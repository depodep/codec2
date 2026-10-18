<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\TelegramAttendanceNotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Illuminate\Support\Str;

class TelegramDispatchController extends Controller
{
    public function adminIndex(Request $request): View
    {
        return $this->index($request, true);
    }

    public function teacherIndex(Request $request): View
    {
        return $this->index($request, false);
    }

    public function sendAbsenceNotifications(
        Request $request,
        TelegramAttendanceNotificationService $notifications
    ): JsonResponse {
        $request->validate([
            'absence_date' => ['required', 'date', 'before_or_equal:today'],
            'selected_sections' => ['sometimes', 'required', 'array', 'min:1'],
            'selected_sections.*' => ['string', 'distinct', 'max:100'],
        ]);
        $date = Carbon::parse($request->input('absence_date', today()->toDateString()));
        $students = $this->absentStudents($request->user(), (int) $request->user()->role_id === 1, $date);
        if ($request->has('selected_sections')) {
            $students = $students->whereIn('section', $request->input('selected_sections'))->values();
        }
        $template = $this->absenceTemplate();
        $summary = ['total' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($students as $student) {
            $summary['total']++;
            $name = trim($student->first_name.' '.$student->last_name);
            $message = strtr($template, [
                '{student_name}' => $name,
                '{date}' => $date->format('F j, Y'),
                '{section}' => $student->section ?: 'Unassigned',
                '{school_name}' => 'Southern Isabela Academy',
            ]);
            $result = $notifications->sendToStudent(
                $student,
                'absence:'.$date->toDateString().':'.$student->id,
                $message,
                null,
                'Absence notification'
            );
            $this->addSummary($summary, $result);
        }

        $dashboard = $this->absenceDashboard(
            $this->absentStudents($request->user(), (int) $request->user()->role_id === 1, $date),
            $date
        );

        AuditLog::record(
            action: 'Sent Absence Notifications',
            module: 'Telegram Notifications',
            status: $summary['failed'] === 0 ? 'Success' : 'Failed',
            description: "Absence notification dispatch for date {$date->toDateString()}: {$summary['sent']} sent, {$summary['failed']} failed, {$summary['skipped']} skipped."
        );

        return response()->json([
            'success' => $summary['failed'] === 0,
            'message' => $summary['total'] === 0
                ? 'No absent students were found for the selected date.'
                : "Telegram absence notifications: {$summary['sent']} sent, {$summary['failed']} failed, {$summary['skipped']} skipped.",
            'summary' => $summary,
            'dashboard' => $dashboard,
        ], $summary['failed'] > 0 && $summary['sent'] === 0 ? 422 : 200);
    }

    public function saveAbsenceTemplate(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'absence_template' => ['required', 'string', 'max:4000'],
        ]);

        if (! Schema::hasTable('settings')) {
            return back()->withErrors(['absence_template' => 'Settings storage is unavailable. The Telegram template was not saved.']);
        }

        DB::table('settings')->updateOrInsert(
            ['key' => 'absence_telegram_template'],
            ['value' => trim($validated['absence_template']), 'created_at' => now(), 'updated_at' => now()]
        );

        AuditLog::record(
            action: 'Updated Absence Template',
            module: 'Telegram Settings',
            status: 'Success',
            description: 'Updated the Telegram absence notification message template.'
        );

        return back()->with('success', 'Telegram absence message template saved.');
    }

    public function saveCustomMessageTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message_template' => ['required', 'string', 'max:4000'],
        ]);

        if (! Schema::hasTable('settings')) {
            return response()->json([
                'success' => false,
                'message' => 'Settings storage is unavailable. The custom message template was not saved.',
            ], 503);
        }

        $role = (int) $request->user()->role_id === 1 ? 'admin' : 'teacher';
        DB::table('settings')->updateOrInsert(
            ['key' => "telegram_custom_message_template_{$role}"],
            ['value' => trim($validated['message_template']), 'created_at' => now(), 'updated_at' => now()]
        );

        AuditLog::record(
            action: 'Updated Custom Message Template',
            module: 'Telegram Settings',
            status: 'Success',
            description: "Updated custom message template for role: {$role}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Custom message template saved.',
        ]);
    }

    public function sendCustomMessage(
        Request $request,
        TelegramAttendanceNotificationService $notifications
    ): JsonResponse {
        $validated = $request->validate([
            'recipient_mode' => ['required', 'in:all_linked,selected_students'],
            'student_ids' => ['required_if:recipient_mode,selected_students', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'distinct'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $isAdmin = (int) $request->user()->role_id === 1;
        $recipients = $this->studentScope($request->user(), $isAdmin)
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('telegram_parent_links')
                    ->whereColumn('telegram_parent_links.student_id', 'users.id')
                    ->where('telegram_parent_links.status', 'approved')
                    ->where('telegram_parent_links.notifications_enabled', true);
            });

        if ($validated['recipient_mode'] === 'selected_students') {
            $recipients->whereIn('users.id', $validated['student_ids']);
        }

        $students = $recipients->orderBy('last_name')->orderBy('first_name')->get();
        if ($students->isEmpty()) {
            AuditLog::record(
                action: 'Sent Custom Message',
                module: 'Telegram Notifications',
                status: 'Failed',
                description: 'Failed custom message dispatch: No recipients with approved Telegram links found.'
            );

            return response()->json([
                'success' => false,
                'message' => 'No selected recipients have an approved Telegram link with notifications enabled.',
                'summary' => ['total' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0],
            ], 422);
        }

        $messageKey = (string) Str::uuid();
        $sender = $request->user();
        $sentAt = Carbon::now();
        $summary = ['total' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($students as $student) {
            $summary['total']++;
            $result = $notifications->sendToStudent(
                $student,
                'custom:'.$messageKey.':'.$student->id,
                $this->renderCustomMessage($validated['message'], $student, $sender, $sentAt),
                null,
                'Custom message'
            );
            $this->addSummary($summary, $result);
        }

        AuditLog::record(
            action: 'Sent Custom Message',
            module: 'Telegram Notifications',
            status: $summary['failed'] === 0 ? 'Success' : 'Failed',
            description: "Telegram custom message dispatch: {$summary['sent']} sent, {$summary['failed']} failed, {$summary['skipped']} skipped."
        );

        return response()->json([
            'success' => $summary['sent'] > 0,
            'message' => "Telegram message: {$summary['sent']} sent, {$summary['failed']} failed, {$summary['skipped']} skipped.",
            'summary' => $summary,
        ], $summary['sent'] > 0 ? 200 : 422);
    }

    private function index(Request $request, bool $isAdmin): View
    {
        $validated = $request->validate([
            'absence_date' => ['nullable', 'date', 'before_or_equal:today'],
        ]);
        $date = Carbon::parse($validated['absence_date'] ?? today()->toDateString());
        $students = $this->studentScope($request->user(), $isAdmin)
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('telegram_parent_links')
                    ->whereColumn('telegram_parent_links.student_id', 'users.id')
                    ->where('telegram_parent_links.status', 'approved')
                    ->where('telegram_parent_links.notifications_enabled', true);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['users.id', 'users.first_name', 'users.last_name', 'users.section', 'users.grade_level']);

        $dashboard = $this->absenceDashboard(
            $this->absentStudents($request->user(), $isAdmin, $date),
            $date
        );

        $studentsList = $students->map(function ($s) {
            return [
                'id' => (int) $s->id,
                'name' => trim($s->first_name.' '.$s->last_name),
                'section' => $s->section ?: '—',
                'grade_level' => $s->grade_level ?: '—',
            ];
        })->values();

        return view('notifications.dispatch', [
            'students' => $studentsList,
            'dashboard' => $dashboard,
            'absenceDate' => $date->toDateString(),
            'absenceTemplate' => $this->absenceTemplate(),
            'customMessageTemplate' => $this->customMessageTemplate($isAdmin),
            'isAdmin' => $isAdmin,
        ]);
    }

    private function customMessageTemplate(bool $isAdmin): string
    {
        $role = $isAdmin ? 'admin' : 'teacher';
        if (Schema::hasTable('settings')) {
            $template = DB::table('settings')
                ->where('key', "telegram_custom_message_template_{$role}")
                ->value('value');
            if (is_string($template) && trim($template) !== '') {
                return $template;
            }
        }

        return 'Good day, parent/guardian of {student_name} ({section}). Notice from {school_name} on {date}: ';
    }

    private function renderCustomMessage(string $template, User $student, User $sender, Carbon $sentAt): string
    {
        $studentName = trim($student->first_name.' '.$student->last_name);
        $senderName = trim($sender->first_name.' '.$sender->last_name);

        return strtr(trim($template), [
            '{name}' => $studentName,
            '{student_name}' => $studentName,
            '{first_name}' => $student->first_name,
            '{last_name}' => $student->last_name,
            '{parent_name}' => trim((string) $student->parent_name) ?: 'Parent/Guardian',
            '{grade_level}' => (string) ($student->grade_level ?: '—'),
            '{grade}' => (string) ($student->grade_level ?: '—'),
            '{section}' => $student->section ?: 'Unassigned',
            '{section_name}' => $student->section ?: 'Unassigned',
            '{date}' => $sentAt->format('F j, Y'),
            '{school_name}' => 'Southern Isabela Academy',
            '{sender_name}' => $senderName,
            '{teacher_name}' => $senderName,
        ]);
    }

    private function absentStudents(User $sender, bool $isAdmin, Carbon $date): Collection
    {
        $query = $this->studentScope($sender, $isAdmin);
        if (Schema::hasTable('attendance_logs') && Schema::hasColumn('attendance_logs', 'user_id')) {
            $presentIds = DB::table('attendance_logs')
                ->whereDate(Schema::hasColumn('attendance_logs', 'attendance_date') ? 'attendance_date' : 'created_at', $date->toDateString())
                ->whereIn('status', ['ON-TIME', 'LATE', 'PRESENT', 'on-time', 'late', 'present'])
                ->select('user_id');
            $query->whereNotIn('users.id', $presentIds);
        }

        return $query->orderBy('last_name')->orderBy('first_name')->get([
            'users.id',
            'users.first_name',
            'users.last_name',
            'users.section',
        ]);
    }

    private function absenceDashboard(Collection $students, Carbon $date): array
    {
        $linksByStudent = DB::table('telegram_parent_links')
            ->whereIn('student_id', $students->pluck('id'))
            ->where('status', 'approved')
            ->where('notifications_enabled', true)
            ->get(['id', 'student_id'])
            ->groupBy('student_id');
        $linkIds = $linksByStudent->flatten(1)->pluck('id')->all();
        $deliveriesByLink = empty($linkIds)
            ? collect()
            : DB::table('telegram_notifications')
                ->where('notification_key', 'like', 'absence:'.$date->toDateString().':%')
                ->whereIn('telegram_parent_link_id', $linkIds)
                ->get(['student_id', 'telegram_parent_link_id', 'status'])
                ->keyBy('telegram_parent_link_id');

        $students->each(function (User $student) use ($linksByStudent, $deliveriesByLink): void {
            $studentLinks = $linksByStudent->get($student->id, collect());
            $deliveries = $studentLinks->mapWithKeys(fn ($link): array => [
                $link->id => $deliveriesByLink->get($link->id),
            ]);
            $sent = $deliveries->filter(fn ($delivery): bool => $delivery?->status === 'sent')->count();
            $failed = $deliveries->filter(fn ($delivery): bool => $delivery?->status === 'failed')->count();
            $sendable = $deliveries->filter(fn ($delivery): bool => $delivery === null || $delivery->status === 'failed')->count();

            $student->setAttribute('has_telegram', $studentLinks->isNotEmpty());
            $student->setAttribute('absence_sent_count', $sent);
            $student->setAttribute('absence_failed_count', $failed);
            $student->setAttribute('absence_sendable_count', $sendable);
            $student->setAttribute('absence_delivery_status', match (true) {
                $sent > 0 && $failed > 0 => 'Partly sent',
                $sent > 0 => 'Sent',
                $failed > 0 => 'Failed; retry available',
                $studentLinks->isEmpty() => 'Not linked',
                $sendable === 0 => 'Already sent',
                default => 'Not sent yet',
            });
        });

        $sections = $students
            ->groupBy(fn (User $student): string => trim((string) $student->section) ?: 'Unassigned')
            ->map(function (Collection $sectionStudents, string $section): array {
                return [
                    'name' => $section,
                    'student_count' => $sectionStudents->count(),
                    'sent' => $sectionStudents->sum('absence_sent_count'),
                    'failed' => $sectionStudents->sum('absence_failed_count'),
                    'sendable_students' => $sectionStudents->where('absence_sendable_count', '>', 0)->count(),
                    'eligible_links' => $sectionStudents->sum('absence_sendable_count'),
                    'linked' => $sectionStudents->where('has_telegram', true)->count(),
                    'unlinked' => $sectionStudents->where('has_telegram', false)->count(),
                    'already_sent' => $sectionStudents->filter(fn (User $student): bool => $student->has_telegram && $student->absence_sendable_count === 0)->count(),
                    'students' => $sectionStudents->map(fn (User $student): array => [
                        'id' => $student->id,
                        'name' => trim($student->first_name.' '.$student->last_name),
                        'linked' => $student->has_telegram,
                        'sendable' => $student->absence_sendable_count > 0,
                        'sent' => $student->absence_sent_count,
                        'failed' => $student->absence_failed_count,
                        'status' => $student->absence_delivery_status,
                    ])->values()->all(),
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return [
            'absent' => $students->count(),
            'sent' => $deliveriesByLink->where('status', 'sent')->count(),
            'failed' => $deliveriesByLink->where('status', 'failed')->count(),
            'linked' => $students->where('has_telegram', true)->count(),
            'unlinked' => $students->where('has_telegram', false)->count(),
            'eligible_links' => $students->sum('absence_sendable_count'),
            'sendable_students' => $students->where('absence_sendable_count', '>', 0)->count(),
            'already_sent' => $students->filter(fn (User $student): bool => $student->has_telegram && $student->absence_sendable_count === 0)->count(),
            'not_sent' => $students->where('absence_sendable_count', '>', 0)->count(),
            'sections' => $sections->all(),
        ];
    }

    private function studentScope(User $sender, bool $isAdmin): Builder
    {
        $query = User::query()->where('users.role_id', 3)->where('users.is_active', true);
        if ($isAdmin) {
            return $query;
        }

        $sectionNames = collect([$sender->section])->filter()->values();
        $sectionIds = collect();
        if (Schema::hasTable('academic_sections')) {
            $advisory = DB::table('academic_sections')->where('advisor_id', $sender->id)->get(['id', 'section_name']);
            $sectionNames = $sectionNames->merge($advisory->pluck('section_name'));
            $sectionIds = $sectionIds->merge($advisory->pluck('id'));
        }
        if (Schema::hasTable('class_schedules')) {
            $schedules = DB::table('class_schedules')->where('teacher_id', $sender->id)->get(['section', 'section_id']);
            $sectionNames = $sectionNames->merge($schedules->pluck('section'));
            $sectionIds = $sectionIds->merge($schedules->pluck('section_id'));
        }
        $sectionNames = $sectionNames->filter()->unique()->values();
        $sectionIds = $sectionIds->filter()->unique()->values();

        if ($sectionNames->isEmpty() && ($sectionIds->isEmpty() || ! Schema::hasTable('section_student'))) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $scope) use ($sectionNames, $sectionIds): void {
            if ($sectionNames->isNotEmpty()) {
                $scope->whereIn('users.section', $sectionNames);
            }
            if ($sectionIds->isNotEmpty() && Schema::hasTable('section_student')) {
                $scope->orWhereIn('users.id', function ($subquery) use ($sectionIds): void {
                    $subquery->select('student_id')
                        ->from('section_student')
                        ->whereIn('section_id', $sectionIds);
                });
            }
        });
    }

    private function absenceTemplate(): string
    {
        if (Schema::hasTable('settings')) {
            $template = DB::table('settings')->where('key', 'absence_telegram_template')->value('value');
            if (! is_string($template) || trim($template) === '') {
                $template = DB::table('settings')->where('key', 'absence_sms_template')->value('value');
            }
            if (is_string($template) && trim($template) !== '') {
                return $template;
            }
        }

        return 'Good day. {student_name} was recorded absent on {date} ({section}). Please contact {school_name} if you have questions.';
    }

    private function addSummary(array &$summary, array $result): void
    {
        $summary['sent'] += $result['sent'];
        $summary['failed'] += $result['failed'];
        $summary['skipped'] += $result['skipped'];
    }
}
