<?php

namespace App\Console\Commands;

use App\Models\AcademicSection;
use App\Models\User;
use App\Services\TelegramAttendanceNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * CheckAdvisoryAttendance
 *
 * Runs once daily (default: 8:30 AM) and notifies parents of students
 * who have NO advisory time-in scan recorded for the current school day.
 * Duplicate prevention uses a Telegram notification key per linked account.
 */
class CheckAdvisoryAttendance extends Command
{
    protected $signature = 'attendance:check-advisory
                            {--date= : Override date to check (Y-m-d). Defaults to today.}
                            {--dry-run : Show who would be notified without sending Telegram messages.}';

    protected $description = 'Notify parents of students with no advisory time-in scan by the cutoff time.';

    public function __construct(private TelegramAttendanceNotificationService $notifications)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->tablesExist()) {
            $this->warn('Advisory attendance check skipped: required tables/columns are missing.');
            return self::SUCCESS;
        }

        $date    = $this->option('date') ?? now()->toDateString();
        $dryRun  = $this->option('dry-run');
        $today   = Carbon::parse($date);

        // Skip weekends
        if ($today->isWeekend()) {
            $this->info("Skipped — {$date} is a weekend.");
            return self::SUCCESS;
        }

        $now = now();
        if ($date === $now->toDateString()) {
            $graceMinutes = $this->gracePeriodMinutes();
            $cutoff = $now->copy()->setTime(8, 30)->addMinutes($graceMinutes);
            if ($now->lt($cutoff)) {
                $this->info("Skipped — advisory absence check begins at {$cutoff->format('g:i A')}.");
                return self::SUCCESS;
            }
        }

        $template = $this->template();
        $sent      = 0;
        $failed    = 0;
        $skipped   = 0;

        // Load all active sections (adviser loaded manually below via User::find)
        $sections = AcademicSection::all();

        if ($sections->isEmpty()) {
            $this->warn('No academic sections found. Nothing to check.');
            return self::SUCCESS;
        }

        foreach ($sections as $section) {
            // Fetch students in this section
            $students = $this->studentsForSection($section);
            if ($students->isEmpty()) {
                Log::debug('Advisory check: no students in section.', [
                    'section_id' => $section->id,
                    'section'    => $section->section_name,
                ]);
                continue;
            }

            // Students who HAVE scanned in today (any status except ABSENT)
            $presentIds = DB::table('attendance_logs')
                ->whereDate('attendance_date', $date)
                ->where('advisory_section_id', $section->id)
                ->where('attendance_type', 'advisory')
                ->whereNotNull('time_in')
                ->whereIn('status', ['ON-TIME', 'LATE', 'PRESENT', 'on-time', 'late', 'present'])
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->unique();

            // Students who are ABSENT = no scan at all today
            $absentStudents = $students->reject(
                fn (User $s) => $presentIds->contains((int) $s->id)
            )->values();

            if ($absentStudents->isEmpty()) {
                Log::debug('Advisory check: all students present.', [
                    'section'    => $section->section_name,
                    'date'       => $date,
                ]);
                continue;
            }

            Log::info('Advisory check: absent students found.', [
                'section' => $section->section_name,
                'date'    => $date,
                'count'   => $absentStudents->count(),
            ]);

            // Notify each absent student's parent
            foreach ($absentStudents as $student) {
                $notifKey = "advisory_absence:{$date}:{$section->id}:{$student->id}";

                $studentName  = $student->full_name ?: trim($student->first_name . ' ' . $student->last_name);
                $sectionName  = $section->section_name;
                $gradeLevel   = $section->grade_level;
                $formattedDate = $today->format('F d, Y');

                $message = $this->render($template, [
                    '{student_name}'  => $studentName,
                    '{section_name}'  => $sectionName,
                    '{grade_level}'   => $gradeLevel,
                    '{date}'          => $formattedDate,
                ]);

                if ($dryRun) {
                    $this->line("[DRY-RUN] Would send a Telegram alert to linked guardians of {$studentName}: {$message}");
                    continue;
                }

                $result = $this->notifications->sendToStudent(
                    $student,
                    $notifKey,
                    $message,
                    null,
                    'Advisory absence notification'
                );
                $sent += $result['sent'];
                $failed += $result['failed'];
                $skipped += $result['skipped'];
            }
        }

        $this->info("Advisory Telegram absence check done for {$date}: {$sent} sent, {$failed} failed, {$skipped} skipped.");
        return self::SUCCESS;
    }

    /**
     * Get all students belonging to the given section via section_student pivot.
     * Falls back to matching the section_name on the users.section column.
     */
    private function studentsForSection(AcademicSection $section): \Illuminate\Support\Collection
    {
        if (Schema::hasTable('section_student')) {
            $studentIds = DB::table('section_student')
                ->where('section_id', $section->id)
                ->pluck('student_id');

            if ($studentIds->isNotEmpty()) {
                return User::where('role_id', 3)
                    ->whereIn('id', $studentIds)
                    ->where('is_active', 1)
                    ->get();
            }
        }

        // Fallback: match by section name string on users table
        return User::where('role_id', 3)
            ->where('section', $section->section_name)
            ->where('is_active', 1)
            ->get();
    }

    /**
     * Check that required tables and columns exist before running.
     */
    private function tablesExist(): bool
    {
        return Schema::hasTable('attendance_logs')
            && Schema::hasColumn('attendance_logs', 'advisory_section_id')
            && Schema::hasColumn('attendance_logs', 'attendance_type')
            && Schema::hasColumn('attendance_logs', 'time_in')
            && Schema::hasTable('academic_sections');
    }

    private function template(): string
    {
        $saved = Schema::hasTable('settings')
            ? DB::table('settings')->where('key', 'telegram_advisory_absence_template')->value('value')
            : null;

        return is_string($saved) && trim($saved) !== ''
            ? $saved
            : 'Advisory notice: {student_name} (Grade {grade_level} - {section_name}) was recorded absent from advisory on {date}. Please contact the school if this is unexpected.';
    }

    private function gracePeriodMinutes(): int
    {
        $value = Schema::hasTable('settings')
            ? DB::table('settings')->where('key', 'advisory_absence_grace_minutes')->value('value')
            : null;
        $minutes = filter_var($value, FILTER_VALIDATE_INT);

        return $minutes !== false && $minutes >= 0 ? $minutes : 0;
    }

    private function render(string $template, array $values): string
    {
        return strtr($template, $values);
    }
}
