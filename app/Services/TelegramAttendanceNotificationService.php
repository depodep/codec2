<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TelegramAttendanceNotificationService
{
    public function __construct(private readonly TelegramBotService $bot) {}

    public function sendForAttendance(User $student, int $attendanceLogId, string $event): void
    {
        $attendance = AttendanceLog::query()->find($attendanceLogId);
        if (! $attendance) {
            Log::warning('Telegram attendance alert skipped because its attendance record was unavailable.', [
                'attendance_log_id' => $attendanceLogId,
            ]);

            return;
        }

        $status = strtoupper((string) $attendance->status) === 'LATE' ? 'Late' : 'Present';
        $time = $event === 'TIME OUT' ? $attendance->time_out : $attendance->time_in;
        $date = Carbon::parse($attendance->getRawOriginal('attendance_date'))->format('M j, Y');
        $message = sprintf(
            'SIATRACK attendance update: %s was marked %s (%s) on %s at %s.',
            trim($student->first_name.' '.$student->last_name),
            $status,
            strtolower($event) === 'time out' ? 'time out' : 'time in',
            $date,
            $time ? date('g:i A', strtotime((string) $time)) : 'an unrecorded time'
        );

        $this->sendToStudent(
            $student,
            "attendance:{$attendanceLogId}:{$event}",
            $message,
            $attendanceLogId,
            'Attendance alert'
        );
    }

    public function sendToStudent(
        User $student,
        string $notificationKey,
        string $message,
        ?int $attendanceLogId = null,
        string $details = 'Telegram message'
    ): array {
        $links = DB::table('telegram_parent_links')
            ->where('student_id', $student->id)
            ->where('status', 'approved')
            ->where('notifications_enabled', true)
            ->get();

        if ($links->isEmpty()) {
            $this->recordSkipped($student, $notificationKey, $attendanceLogId);

            return ['sent' => 0, 'failed' => 0, 'skipped' => 1];
        }

        $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($links as $link) {
            $key = "{$notificationKey}:{$link->id}";
            $inserted = DB::table('telegram_notifications')->insertOrIgnore([
                'notification_key' => $key,
                'attendance_log_id' => $attendanceLogId,
                'student_id' => $student->id,
                'telegram_parent_link_id' => $link->id,
                'telegram_chat_id' => $link->telegram_chat_id,
                'status' => 'pending',
                'details' => $details,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted !== 1) {
                $retryClaimed = DB::table('telegram_notifications')
                    ->where('notification_key', $key)
                    ->where('status', 'failed')
                    ->update([
                        'status' => 'pending',
                        'details' => $details,
                        'updated_at' => now(),
                    ]);

                if ($retryClaimed !== 1) {
                    $result['skipped']++;
                    continue;
                }
            }

            try {
                $this->bot->sendMessage((string) $link->telegram_chat_id, $message);
                DB::table('telegram_notifications')->where('notification_key', $key)->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'updated_at' => now(),
                ]);
                $result['sent']++;
            } catch (RuntimeException $exception) {
                DB::table('telegram_notifications')->where('notification_key', $key)->update([
                    'status' => 'failed',
                    'details' => 'Telegram delivery failed. Check bot configuration and recipient access.',
                    'updated_at' => now(),
                ]);
                $result['failed']++;
                \App\Models\AuditLog::record(
                    action: 'Failed Telegram Alert',
                    module: 'Telegram Notifications',
                    status: 'Failed',
                    description: "Telegram alert failed for {$student->first_name} {$student->last_name} (Chat ID: {$link->telegram_chat_id}): {$exception->getMessage()}",
                    user: $student
                );
                Log::warning('Telegram attendance alert delivery failed.', [
                    'notification_key' => $key,
                    'student_id' => $student->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        return $result;
    }

    private function recordSkipped(User $student, string $notificationKey, ?int $attendanceLogId): void
    {
        DB::table('telegram_notifications')->insertOrIgnore([
            'notification_key' => "{$notificationKey}:unlinked",
            'attendance_log_id' => $attendanceLogId,
            'student_id' => $student->id,
            'status' => 'skipped',
            'details' => 'No approved Telegram link with notifications enabled.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
