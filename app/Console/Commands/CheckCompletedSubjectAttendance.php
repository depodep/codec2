<?php

namespace App\Console\Commands;

use App\Models\ClassSchedule;
use App\Models\User;
use App\Services\TelegramAttendanceNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CheckCompletedSubjectAttendance extends Command
{
    protected $signature = 'attendance:check-completed-subjects';

    protected $description = 'Notify emergency contacts and class advisers about missing subject attendance.';

    public function __construct(private TelegramAttendanceNotificationService $notifications)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!$this->attendanceDataIsUsable()) {
            $this->warn('Attendance monitoring skipped because required attendance fields are unavailable.');
            return self::SUCCESS;
        }

        $now = now();
        $date = $now->toDateString();
        $day = strtolower($now->format('D'));
        $graceMinutes = $this->gracePeriodMinutes();
        $template = $this->template();
        $processed = 0;
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        $schedules = ClassSchedule::with(['academicSection', 'subjectRecord'])
            ->whereNotNull('end_time')
            ->get()
            ->filter(function (ClassSchedule $schedule) use ($now, $day, $graceMinutes): bool {
                $scheduledDay = strtolower(trim((string) ($schedule->day ?? $schedule->day_of_week ?? '')));
                if ($scheduledDay === '' || substr($scheduledDay, 0, 3) !== $day) {
                    return false;
                }

                try {
                    return Carbon::parse($now->toDateString() . ' ' . $schedule->start_time)
                        ->addMinutes($graceMinutes)
                        ->lte($now);
                } catch (\Throwable) {
                    return false;
                }
            });

        foreach ($schedules as $schedule) {
            $students = $this->studentsForSchedule($schedule);
            if ($students->isEmpty()) {
                continue;
            }

            $presentIds = DB::table('attendance_logs')
                ->whereDate('attendance_date', $date)
                ->where('class_schedule_id', $schedule->id)
                ->whereIn('attendance_type', ['subject', 'SUBJECT'])
                ->whereIn('status', ['ON-TIME', 'LATE', 'PRESENT', 'on-time', 'late', 'present'])
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->unique();

            // A completely empty scan set is treated as incomplete data, not an all-absent class.
            if ($presentIds->isEmpty()) {
                Log::debug('Subject absence check skipped: no valid attendance records.', [
                    'schedule_id' => $schedule->id,
                    'date' => $date,
                ]);
                continue;
            }

            $absentStudents = $students->reject(fn (User $student) => $presentIds->contains((int) $student->id))->values();
            if ($absentStudents->isEmpty()) {
                continue;
            }

            $processed++;
            $sectionName = $schedule->academicSection?->section_name ?: ($schedule->section ?: 'Unassigned');
            $subjectName = $schedule->subjectRecord?->name ?: ($schedule->subject_name ?: 'Subject');
            $startTime = $this->formatTime($schedule->start_time);
            $endTime = $this->formatTime($schedule->end_time);

            foreach ($absentStudents as $student) {
                $message = $this->render($template, [
                    '{student_name}' => $student->full_name ?: trim($student->first_name . ' ' . $student->last_name),
                    '{subject_name}' => $subjectName,
                    '{date}' => $now->format('F d, Y'),
                    '{time}' => $endTime,
                    '{start_time}' => $startTime,
                    '{end_time}' => $endTime,
                    '{section_name}' => $sectionName,
                    '{section}' => $sectionName,
                ]);

                $result = $this->notifications->sendToStudent(
                    $student,
                    "subject_absence:{$date}:{$schedule->id}:{$student->id}",
                    $message,
                    null,
                    'Subject absence notification'
                );
                $sent += $result['sent'];
                $failed += $result['failed'];
                $skipped += $result['skipped'];
            }
        }

        $this->info("Checked {$processed} completed subject(s): {$sent} Telegram messages sent, {$failed} failed, {$skipped} skipped.");
        return self::SUCCESS;
    }

    private function studentsForSchedule(ClassSchedule $schedule)
    {
        if ($schedule->section_id && Schema::hasTable('section_student')) {
            return User::where('role_id', 3)
                ->whereIn('id', DB::table('section_student')->where('section_id', $schedule->section_id)->pluck('student_id'))
                ->get();
        }

        $sectionName = trim((string) ($schedule->section ?: $schedule->academicSection?->section_name));
        return $sectionName === ''
            ? collect()
            : User::where('role_id', 3)->where('section', $sectionName)->get();
    }

    private function attendanceDataIsUsable(): bool
    {
        return Schema::hasTable('attendance_logs')
            && Schema::hasColumn('attendance_logs', 'user_id')
            && Schema::hasColumn('attendance_logs', 'attendance_date')
            && Schema::hasColumn('attendance_logs', 'class_schedule_id')
            && Schema::hasColumn('attendance_logs', 'attendance_type')
            && Schema::hasColumn('attendance_logs', 'status');
    }

    private function template(): string
    {
        $saved = Schema::hasTable('settings')
            ? DB::table('settings')->where('key', 'telegram_subject_absence_template')->value('value')
            : null;

        return is_string($saved) && trim($saved) !== ''
            ? $saved
            : 'Your child, {student_name}, was recorded absent from {subject_name} on {date}, scheduled from {start_time} to {end_time}. Please contact the school for confirmation.';
    }

    private function gracePeriodMinutes(): int
    {
        $value = Schema::hasTable('settings')
            ? DB::table('settings')->where('key', 'subject_absence_grace_minutes')->value('value')
            : null;
        $minutes = filter_var($value, FILTER_VALIDATE_INT);

        return $minutes !== false && $minutes >= 0 ? $minutes : 30;
    }

    private function render(string $template, array $values): string
    {
        return strtr($template, $values);
    }

    private function formatTime(?string $time): string
    {
        return $time ? Carbon::parse($time)->format('g:i A') : 'the scheduled time';
    }
}
