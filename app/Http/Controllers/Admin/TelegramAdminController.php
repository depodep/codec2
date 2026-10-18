<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\TelegramStudentLinkCodeService;
use App\Services\TelegramBotService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class TelegramAdminController extends Controller
{
    public function index(Request $request, TelegramBotService $bot): View
    {
        $linksQuery = DB::table('telegram_parent_links as links')
            ->join('users', 'users.id', '=', 'links.student_id')
            ->leftJoin('users as approvers', 'approvers.id', '=', 'links.approved_by')
            ->select(
                'links.*',
                'users.first_name',
                'users.last_name',
                'users.id_number',
                'users.grade_level',
                'users.section',
                'approvers.first_name as approver_first_name',
                'approvers.last_name as approver_last_name'
            );
        $this->applyStudentFilters($linksQuery, $request, 'links.telegram_chat_id', 'parent_');
        $links = $linksQuery
            ->orderByRaw("CASE WHEN links.status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('links.created_at')
            ->paginate(10, ['*'], 'parent_links_page')
            ->withQueryString();

        $studentCodes = $this->studentCodeQuery($request)
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->paginate(10, ['*'], 'student_codes_page')
            ->withQueryString();

        $notifications = DB::table('telegram_notifications')
            ->join('users', 'users.id', '=', 'telegram_notifications.student_id')
            ->select(
                'telegram_notifications.*',
                'users.first_name',
                'users.last_name'
            )
            ->latest('telegram_notifications.created_at')
            ->paginate(10, ['*'], 'notifications_page')
            ->withQueryString();

        $studentLinkCounts = $this->studentLinkCounts();
        $parentLinkCount = DB::table('telegram_parent_links')->count();

        $gradeLevels = DB::table('users')
            ->where('role_id', 3)
            ->whereNotNull('grade_level')
            ->where('grade_level', '!=', '')
            ->distinct()
            ->orderBy('grade_level')
            ->pluck('grade_level');
        $sections = DB::table('users')
            ->where('role_id', 3)
            ->whereNotNull('section')
            ->where('section', '!=', '')
            ->select('grade_level', 'section as section_name')
            ->distinct()
            ->orderBy('grade_level')
            ->orderBy('section_name')
            ->get();
        $teachers = DB::table('users')
            ->where('role_id', 2)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $viewData = [
            'links' => $links,
            'studentCodes' => $studentCodes,
            'notifications' => $notifications,
            'gradeLevels' => $gradeLevels,
            'sections' => $sections,
            'teachers' => $teachers,
            'botEnabled' => $bot->enabled(),
            'botConfigured' => $bot->configured(),
            'webhookConfigured' => filled(config('services.telegram.webhook_secret')),
            'studentLinkCounts' => $studentLinkCounts,
            'parentLinkCount' => $parentLinkCount,
        ];

        if ($request->ajax()) {
            return view('admin.telegram.partials.content', $viewData);
        }

        return view('admin.telegram.index', $viewData);
    }

    public function regenerateStudentCode(Request $request, int $studentId, TelegramStudentLinkCodeService $codes): JsonResponse|RedirectResponse
    {
        $student = User::query()->where('role_id', 3)->findOrFail($studentId);
        $code = $codes->regenerateForStudent((int) $student->id);

        $result = [
            'success' => true,
            'code' => $code->code,
            'message' => 'New Telegram linking code generated. The previous code is no longer valid.',
            'student_name' => trim($student->first_name.' '.$student->last_name),
        ];

        AuditLog::record(
            action: 'Regenerated Student Link Code',
            module: 'Telegram Settings',
            status: 'Success',
            description: "Regenerated Telegram link code for student #{$studentId} ({$result['student_name']})."
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()
            ->route('admin.telegram.index')
            ->with('regeneratedTelegramCode', $result);
    }

    public function exportStudentCodes(Request $request)
    {
        $students = $this->studentCodeQuery($request)
            ->orderBy('users.grade_level')
            ->orderBy('users.section')
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->get();

        AuditLog::record(
            action: 'Exported Student Link Codes',
            module: 'Telegram Settings',
            status: 'Success',
            description: "Exported {$students->count()} student Telegram linking codes as CSV."
        );

        return response()->streamDownload(function () use ($students): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Unable to prepare the student linking code export.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Student Name', 'LRN', 'Grade', 'Section', 'Telegram Linking Code']);
            foreach ($students as $student) {
                fputcsv($output, [
                    trim($student->first_name.' '.$student->last_name),
                    $student->id_number,
                    $student->grade_level,
                    $student->section,
                    $student->code,
                ]);
            }
            fclose($output);
        }, 'telegram-student-link-codes.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function studentCodeQuery(Request $request)
    {
        $query = DB::table('telegram_student_link_codes as codes')
            ->join('users', 'users.id', '=', 'codes.student_id')
            ->select(
                'codes.id as code_id',
                'codes.code',
                'users.id as student_id',
                'users.first_name',
                'users.last_name',
                'users.id_number',
                'users.grade_level',
                'users.section'
            )
            ->addSelect([
                'has_telegram_link' => DB::table('telegram_parent_links')
                    ->selectRaw('1')
                    ->whereColumn('telegram_parent_links.student_id', 'users.id')
                    ->where('telegram_parent_links.status', 'approved')
                    ->limit(1),
            ]);

        $this->applyStudentFilters($query, $request, 'codes.code', 'student_');
        $status = $request->query('student_status');
        if ($status === 'linked') {
            $query->whereExists(
                DB::table('telegram_parent_links as status_links')
                    ->selectRaw('1')
                    ->whereColumn('status_links.student_id', 'users.id')
                    ->where('status_links.status', 'approved')
            );
        } elseif ($status === 'unlinked') {
            $query->whereNotExists(
                DB::table('telegram_parent_links as status_links')
                    ->selectRaw('1')
                    ->whereColumn('status_links.student_id', 'users.id')
                    ->where('status_links.status', 'approved')
            );
        }

        return $query;
    }

    private function studentLinkCounts(): array
    {
        $query = DB::table('telegram_student_link_codes as codes')
            ->join('users', 'users.id', '=', 'codes.student_id');

        $approvedLinks = DB::table('telegram_parent_links as approved_links')
            ->selectRaw('1')
            ->whereColumn('approved_links.student_id', 'users.id')
            ->where('approved_links.status', 'approved');

        return [
            'linked' => (clone $query)->whereExists(clone $approvedLinks)->count(),
            'unlinked' => (clone $query)->whereNotExists(clone $approvedLinks)->count(),
        ];
    }

    private function applyStudentFilters($query, Request $request, string $searchColumn, string $prefix = ''): void
    {
        $search = trim((string) $request->query($prefix.'search', ''));
        $grade = trim((string) $request->query($prefix.'grade', ''));
        $section = trim((string) $request->query($prefix.'section', ''));
        $teacherId = (int) $request->query($prefix.'teacher', 0);

        $query->when($search !== '', function ($builder) use ($search, $searchColumn): void {
            $builder->where(function ($filter) use ($search, $searchColumn): void {
                $filter->where($searchColumn, 'like', "%{$search}%")
                    ->orWhere('users.first_name', 'like', "%{$search}%")
                    ->orWhere('users.last_name', 'like', "%{$search}%")
                    ->orWhere('users.id_number', 'like', "%{$search}%");
            });
        })
            ->when($grade !== '', fn ($builder) => $builder->where('users.grade_level', $grade))
            ->when($section !== '', fn ($builder) => $builder->where('users.section', $section));

        if ($teacherId <= 0) {
            return;
        }

        $sectionNames = $this->teacherSectionNames($teacherId);
        $sectionIds = $this->teacherSectionIds($teacherId);
        $query->where(function ($scope) use ($sectionNames, $sectionIds): void {
            if ($sectionNames->isNotEmpty()) {
                $scope->whereIn('users.section', $sectionNames);
            }
            if ($sectionIds->isNotEmpty() && Schema::hasTable('section_student')) {
                $scope->orWhereIn('users.id', function ($students) use ($sectionIds): void {
                    $students->select('student_id')
                        ->from('section_student')
                        ->whereIn('section_id', $sectionIds);
                });
            }
            if ($sectionNames->isEmpty() && $sectionIds->isEmpty()) {
                $scope->whereRaw('1 = 0');
            }
        });
    }

    private function teacherSectionNames(int $teacherId): Collection
    {
        if (! DB::table('users')->where('id', $teacherId)->where('role_id', 2)->exists()) {
            return collect();
        }

        $sections = collect([
            DB::table('users')->where('id', $teacherId)->value('section'),
        ]);
        if (Schema::hasTable('academic_sections')) {
            $sections = $sections->merge(
                DB::table('academic_sections')->where('advisor_id', $teacherId)->pluck('section_name')
            );
        }
        if (Schema::hasTable('class_schedules')) {
            $sections = $sections->merge(
                DB::table('class_schedules')->where('teacher_id', $teacherId)->pluck('section')
            );
        }

        return $sections->filter()->unique()->values();
    }

    private function teacherSectionIds(int $teacherId): Collection
    {
        if (! DB::table('users')->where('id', $teacherId)->where('role_id', 2)->exists()) {
            return collect();
        }

        $sectionIds = collect();
        if (Schema::hasTable('academic_sections')) {
            $sectionIds = $sectionIds->merge(
                DB::table('academic_sections')->where('advisor_id', $teacherId)->pluck('id')
            );
        }
        if (Schema::hasTable('class_schedules')) {
            $sectionIds = $sectionIds->merge(
                DB::table('class_schedules')->where('teacher_id', $teacherId)->pluck('section_id')
            );
        }

        return $sectionIds->filter()->unique()->values();
    }

    public function testConnection(Request $request, TelegramBotService $bot): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        try {
            $identity = $bot->getMe();
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : back()->withErrors(['telegram' => $message]);
        }

        $message = 'Telegram bot connection verified'.
            (filled($identity['username'] ?? null) ? ' for @'.$identity['username'] : '').'.';

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $message])
            : back()->with('success', $message);
    }

    public function approve(int $id, Request $request, TelegramBotService $bot): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $link = DB::table('telegram_parent_links')->where('id', $id)->where('status', 'pending')->first();
        if (! $link) {
            $message = 'That pending link request is no longer available.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : back()->withErrors(['telegram' => $message]);
        }

        DB::table('telegram_parent_links')->where('id', $id)->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'revoked_at' => null,
            'updated_at' => now(),
        ]);

        try {
            $bot->sendMessage((string) $link->telegram_chat_id, 'The school approved your SIATRACK link. Enabled attendance notifications will now be sent to this Telegram account.');
        } catch (RuntimeException $exception) {
            Log::warning('Telegram link approval notice could not be delivered.', [
                'link_id' => $id,
                'exception' => $exception::class,
            ]);

            $message = 'Link approved. Telegram could not deliver the confirmation message; the approved link is active.';

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $message])
                : back()->with('success', $message);
        }

        AuditLog::record(
            action: 'Approved Telegram Link',
            module: 'Telegram Notifications',
            status: 'Success',
            description: "Approved Telegram link #{$id} for chat ID {$link->telegram_chat_id} (Student ID: {$link->student_id})."
        );

        $message = 'Link approved and confirmation sent.';

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $message])
            : back()->with('success', $message);
    }

    public function revoke(int $id, Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $link = DB::table('telegram_parent_links')->where('id', $id)
            ->whereIn('status', ['pending', 'approved'])
            ->first(['id', 'status']);
        $wasPending = $link?->status === 'pending';
        $updated = $link ? DB::table('telegram_parent_links')->where('id', $id)
            ->whereIn('status', ['pending', 'approved'])
            ->update([
                'status' => 'revoked',
                'notifications_enabled' => false,
                'revoked_at' => now(),
                'updated_at' => now(),
            ]) : 0;

        if ($updated) {
            AuditLog::record(
                action: $wasPending ? 'Declined Telegram Link Request' : 'Revoked Telegram Link',
                module: 'Telegram Notifications',
                status: 'Success',
                description: $wasPending
                    ? "Declined Telegram parent link request #{$id}."
                    : "Revoked Telegram parent link #{$id}."
            );
        }

        $message = $updated
            ? ($wasPending ? 'Telegram link request declined.' : 'Telegram link revoked.')
            : 'That Telegram link is already revoked or unavailable.';

        return $request->expectsJson()
            ? response()->json(['success' => (bool) $updated, 'message' => $message], $updated ? 200 : 422)
            : ($updated ? back()->with('success', $message) : back()->withErrors(['telegram' => $message]));
    }

    public function toggleNotifications(int $id, Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $link = DB::table('telegram_parent_links')->where('id', $id)->where('status', 'approved')->first();
        if (! $link) {
            $message = 'Only approved links can change notification settings.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : back()->withErrors(['telegram' => $message]);
        }

        $newPref = ! $link->notifications_enabled;
        DB::table('telegram_parent_links')->where('id', $id)->update([
            'notifications_enabled' => $newPref,
            'updated_at' => now(),
        ]);

        AuditLog::record(
            action: 'Toggled Telegram Notifications',
            module: 'Telegram Notifications',
            status: 'Success',
            description: "Toggled Telegram notifications for link #{$id} to " . ($newPref ? 'Enabled' : 'Disabled') . '.'
        );

        $message = 'Notification preference updated.';

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $message])
            : back()->with('success', $message);
    }
}
