<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ManagementPortalController extends Controller
{
    public function dashboard(): View
    {
        $user = auth()->user();
        abort_unless((int) $user->role_id === 4, 403);

        $canViewMasterData = (bool) $user->can_view_master_data;
        $canEvaluateFaculty = (bool) $user->can_evaluate_faculty;
        $metrics = [
            'studentCount' => 0,
            'teacherCount' => 0,
            'overallAttendanceRate' => 0,
            'todaySectionAttendance' => collect(),
        ];
        $students = User::query()->whereRaw('1 = 0')->paginate(15, ['*'], 'students_page');
        $teachers = User::query()->whereRaw('1 = 0')->paginate(15, ['*'], 'teachers_page');
        $sections = collect();
        $schedules = collect();

        if ($canViewMasterData) {
            $metrics['studentCount'] = User::query()->where('role_id', 3)->count();
            $metrics['teacherCount'] = User::query()->where('role_id', 2)->count();
            $students = User::query()
                ->where('role_id', 3)
                ->orderBy('section')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(15, ['id', 'first_name', 'last_name', 'grade_level', 'section'], 'students_page')
                ->withQueryString();
            $teachers = User::query()
                ->where('role_id', 2)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(15, ['id', 'first_name', 'last_name', 'id_number', 'email', 'section'], 'teachers_page')
                ->withQueryString();

            if (Schema::hasTable('academic_sections')) {
                $sections = DB::table('academic_sections')
                    ->leftJoin('users as advisers', 'academic_sections.advisor_id', '=', 'advisers.id')
                    ->orderBy('academic_sections.grade_level')
                    ->orderBy('academic_sections.section_name')
                    ->get([
                        'academic_sections.id',
                        'academic_sections.grade_level',
                        'academic_sections.strand',
                        'academic_sections.section_name',
                        'advisers.first_name as adviser_first_name',
                        'advisers.last_name as adviser_last_name',
                    ]);
            }

            if (Schema::hasTable('class_schedules')) {
                $schedules = DB::table('class_schedules')
                    ->leftJoin('users as teachers', 'class_schedules.teacher_id', '=', 'teachers.id')
                    ->leftJoin('academic_sections', 'class_schedules.section_id', '=', 'academic_sections.id')
                    ->orderBy('class_schedules.day')
                    ->orderBy('class_schedules.start_time')
                    ->paginate(15, [
                        'class_schedules.id',
                        'class_schedules.day',
                        'class_schedules.start_time',
                        'class_schedules.end_time',
                        'class_schedules.subject_name',
                        'class_schedules.subject_code',
                        'class_schedules.section',
                        'class_schedules.grade_level',
                        'academic_sections.section_name as academic_section_name',
                        'academic_sections.grade_level as academic_grade_level',
                        'teachers.first_name as teacher_first_name',
                        'teachers.last_name as teacher_last_name',
                    ], 'schedules_page')
                    ->withQueryString();
            }

            if (Schema::hasTable('attendance_logs')) {
                $statusColumn = Schema::hasColumn('attendance_logs', 'status');
                if ($statusColumn) {
                    $attendanceSummary = DB::table('attendance_logs')
                        ->join('users', 'users.id', '=', Schema::hasColumn('attendance_logs', 'student_id') ? 'attendance_logs.student_id' : 'attendance_logs.user_id')
                        ->where('users.role_id', 3)
                        ->selectRaw('COUNT(*) as total, SUM(CASE WHEN UPPER(status) IN (?, ?, ?, ?) THEN 1 ELSE 0 END) as attended', ['PRESENT', 'ON-TIME', 'ON TIME', 'LATE'])
                        ->first();
                    $metrics['overallAttendanceRate'] = $attendanceSummary->total > 0
                        ? round(((int) $attendanceSummary->attended / (int) $attendanceSummary->total) * 100, 1)
                        : 0;
                }

                if (Schema::hasColumn('attendance_logs', 'attendance_date') && $statusColumn) {
                    $studentKey = Schema::hasColumn('attendance_logs', 'student_id') ? 'student_id' : 'user_id';
                    $todayLogs = DB::table('attendance_logs')
                        ->join('users', 'users.id', '=', 'attendance_logs.'.$studentKey)
                        ->where('users.role_id', 3)
                        ->whereDate('attendance_logs.attendance_date', today())
                        ->get(['users.id as student_id', 'users.section', 'attendance_logs.status'])
                        ->groupBy('student_id');
                    $enrollment = User::query()
                        ->where('role_id', 3)
                        ->selectRaw("COALESCE(NULLIF(section, ''), 'Unassigned') as section_name, COUNT(*) as total")
                        ->groupBy('section_name')
                        ->pluck('total', 'section_name');
                    $statusesBySection = $todayLogs
                        ->groupBy(fn ($logs) => trim((string) ($logs->first()->section ?? '')) ?: 'Unassigned')
                        ->map(function ($logs): array {
                            $presentStatuses = ['PRESENT', 'ON-TIME', 'ON TIME'];
                            $present = $logs->filter(fn ($rows) => $rows->contains(fn ($row) => in_array(strtoupper(trim((string) $row->status)), $presentStatuses, true)))->count();
                            $late = $logs->filter(fn ($rows) => !$rows->contains(fn ($row) => in_array(strtoupper(trim((string) $row->status)), $presentStatuses, true))
                                && $rows->contains(fn ($row) => strtoupper(trim((string) $row->status)) === 'LATE'))->count();
                            $absent = $logs->filter(fn ($rows) => !$rows->contains(fn ($row) => in_array(strtoupper(trim((string) $row->status)), $presentStatuses, true))
                                && !$rows->contains(fn ($row) => strtoupper(trim((string) $row->status)) === 'LATE')
                                && $rows->contains(fn ($row) => strtoupper(trim((string) $row->status)) === 'ABSENT'))->count();
                            return ['present' => $present, 'late' => $late, 'absent' => $absent];
                        });

                    $metrics['todaySectionAttendance'] = $enrollment
                        ->map(function ($enrolled, $sectionName) use ($statusesBySection) {
                            $counts = $statusesBySection->get($sectionName, ['present' => 0, 'late' => 0, 'absent' => 0]);
                            return (object) [
                                'section' => $sectionName,
                                'enrolled' => (int) $enrolled,
                                'present' => $counts['present'],
                                'late' => $counts['late'],
                                'absent' => $counts['absent'],
                                'unrecorded' => max(0, (int) $enrolled - $counts['present'] - $counts['late'] - $counts['absent']),
                            ];
                        })
                        ->sortBy('section')
                        ->values();
                }
            }
        }

        $activeCycle = Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')->where('status', 'active')->where('is_active', 1)->first()
            : null;

        return view('management.dashboard', compact(
            'canViewMasterData',
            'canEvaluateFaculty',
            'metrics',
            'students',
            'teachers',
            'sections',
            'schedules',
            'activeCycle'
        ));
    }

    public function evaluations(): View
    {
        $this->authorizeFacultyEvaluation();

        $activeCycle = $this->activeCycle();
        $submissions = Schema::hasTable('evaluation_submissions') && $activeCycle
            ? DB::table('evaluation_submissions')
                ->join('users as teachers', 'teachers.id', '=', 'evaluation_submissions.teacher_id')
                ->where('evaluation_submissions.form_type', 'principal')
                ->where('evaluation_submissions.evaluator_id', auth()->id())
                ->where('evaluation_submissions.evaluation_cycle_id', $activeCycle->id)
                ->orderBy('teachers.last_name')
                ->orderBy('teachers.first_name')
                ->get([
                    'teachers.id as teacher_id',
                    'teachers.first_name',
                    'teachers.last_name',
                    'evaluation_submissions.average_score',
                ])
                ->keyBy('teacher_id')
            : collect();

        $faculty = User::query()
            ->where('role_id', 2)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'id_number', 'email', 'section']);

        return view('management.evaluations.index', compact('activeCycle', 'faculty', 'submissions'));
    }

    public function evaluationForm(int $teacherId): View|RedirectResponse
    {
        $this->authorizeFacultyEvaluation();
        $activeCycle = $this->activeCycle();
        if (!$activeCycle) {
            return redirect()->route('management.evaluations.index')->with('error', 'There is no active faculty evaluation cycle.');
        }

        $teacher = User::query()->where('role_id', 2)->findOrFail($teacherId);
        $alreadySubmitted = DB::table('evaluation_submissions')
            ->where('form_type', 'principal')
            ->where('evaluator_id', auth()->id())
            ->where('teacher_id', $teacher->id)
            ->where('evaluation_cycle_id', $activeCycle->id)
            ->exists();
        if ($alreadySubmitted) {
            return redirect()->route('management.evaluations.index')->with('error', 'You have already submitted a Principal Evaluation for this faculty member in this cycle.');
        }

        $form = DB::table('evaluation_forms')
            ->where('form_type', 'principal')
            ->where('is_active', 1)
            ->orderByDesc('version')
            ->first();
        $questionQuery = DB::table('evaluation_questions')
            ->where('form_type', 'principal')
            ->where('is_active', 1);
        if ($form && Schema::hasColumn('evaluation_questions', 'form_id')) {
            $questionQuery->where('form_id', $form->id);
        }
        $questions = $questionQuery->orderBy('order_num')->get();
        abort_if($questions->isEmpty(), 404, 'The Principal Evaluation form has no active questions.');

        return view('management.evaluations.form', compact('teacher', 'activeCycle', 'form', 'questions'));
    }

    public function storeEvaluation(Request $request, int $teacherId): RedirectResponse
    {
        $this->authorizeFacultyEvaluation();
        $activeCycle = $this->activeCycle();
        if (!$activeCycle) {
            return redirect()->route('management.evaluations.index')->with('error', 'There is no active faculty evaluation cycle.');
        }
        $teacher = User::query()->where('role_id', 2)->findOrFail($teacherId);

        $form = DB::table('evaluation_forms')
            ->where('form_type', 'principal')
            ->where('is_active', 1)
            ->orderByDesc('version')
            ->first();
        $questionQuery = DB::table('evaluation_questions')
            ->where('form_type', 'principal')
            ->where('is_active', 1);
        if ($form && Schema::hasColumn('evaluation_questions', 'form_id')) {
            $questionQuery->where('form_id', $form->id);
        }
        $questions = $questionQuery->get(['id', 'type', 'is_required']);
        if ($questions->isEmpty()) {
            abort(404, 'The Principal Evaluation form has no active questions.');
        }

        $rules = ['comments' => ['nullable', 'string', 'max:2000']];
        foreach ($questions as $question) {
            $rules["answers.{$question->id}"] = $question->is_required
                ? ['required', 'integer', 'between:1,5']
                : ['nullable', 'integer', 'between:1,5'];
        }
        $validated = $request->validate($rules);
        $answers = $validated['answers'] ?? [];
        $numericScores = array_map('intval', array_filter($answers, 'is_numeric'));
        if (count($numericScores) !== $questions->where('is_required', true)->count() && $questions->every(fn ($question) => $question->is_required)) {
            throw ValidationException::withMessages(['answers' => 'Please rate every required item in the Principal Evaluation form.']);
        }
        $academicPeriodId = Schema::hasTable('academic_periods')
            ? DB::table('academic_periods')->where('is_active', 1)->value('id')
            : null;
        if (Schema::hasColumn('evaluation_submissions', 'academic_period_id') && !$academicPeriodId) {
            throw ValidationException::withMessages(['evaluation' => 'An active academic period is required before submitting a faculty evaluation.']);
        }

        DB::transaction(function () use ($teacher, $activeCycle, $answers, $numericScores, $validated, $academicPeriodId): void {
            User::query()->whereKey($teacher->id)->lockForUpdate()->firstOrFail();
            $duplicate = DB::table('evaluation_submissions')
                ->where('form_type', 'principal')
                ->where('evaluator_id', auth()->id())
                ->where('teacher_id', $teacher->id)
                ->where('evaluation_cycle_id', $activeCycle->id)
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['evaluation' => 'You have already submitted this Principal Evaluation for the active cycle.']);
            }

            DB::table('evaluation_submissions')->insert([
                'evaluation_cycle_id' => $activeCycle->id,
                'academic_period_id' => $academicPeriodId,
                'form_type' => 'principal',
                'student_id' => null,
                'evaluator_id' => auth()->id(),
                'teacher_id' => $teacher->id,
                'average_score' => count($numericScores) > 0 ? round(array_sum($numericScores) / count($numericScores), 2) : 0,
                'comments' => $validated['comments'] ?? null,
                'status' => 'SUBMITTED',
                'answers' => json_encode($answers, JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('management.evaluations.index')->with('success', 'Principal Evaluation submitted for '.$teacher->first_name.' '.$teacher->last_name.'.');
    }

    private function authorizeFacultyEvaluation(): void
    {
        abort_unless((int) auth()->user()->role_id === 4 && (bool) auth()->user()->can_evaluate_faculty, 403);
    }

    private function activeCycle(): ?object
    {
        if (!Schema::hasTable('evaluation_cycles')) {
            return null;
        }

        return DB::table('evaluation_cycles')
            ->where('status', 'active')
            ->where('is_active', 1)
            ->first();
    }
}
