<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ClassSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class TeacherDashboardController extends Controller
{
    public function classes(Request $request)
    {
        $teacher = Auth::user();
        $search = trim((string) $request->query('search'));
        $selectedSection = trim((string) $request->query('section'));
        $viewMode = $request->query('view', 'timetable');

        // 1. Get Active School Year / Period
        $activeSchoolYear = '2025-2026';
        $schoolYears = collect([]);
        if (Schema::hasTable('academic_periods')) {
            $schoolYears = DB::table('academic_periods')->orderBy('updated_at', 'desc')->get();
            $activePeriod = $schoolYears->firstWhere('is_active', 1);
            if ($activePeriod) {
                $activeSchoolYear = $activePeriod->name ?? $activePeriod->academic_year ?? $activePeriod->school_year ?? '2025-2026';
            }
        } elseif (Schema::hasTable('school_years')) {
            $schoolYears = DB::table('school_years')->orderBy('start_date', 'desc')->get();
            $activeYear = $schoolYears->firstWhere('status', 'Active') ?? $schoolYears->first();
            if ($activeYear) {
                $activeSchoolYear = $activeYear->name ?? $activeYear->school_year ?? $activeYear->academic_year ?? '2025-2026';
            }
        }

        // 2. Get Sections (Directory)
        $sections = collect([]);
        if (Schema::hasTable('academic_sections')) {
            $sortCol = Schema::hasColumn('academic_sections', 'section_name') ? 'section_name' : (Schema::hasColumn('academic_sections', 'name') ? 'name' : 'id');
            $sections = DB::table('academic_sections')->orderBy($sortCol, 'asc')->get();
        } elseif (Schema::hasTable('sections')) {
            $sortCol = Schema::hasColumn('sections', 'section_name') ? 'section_name' : (Schema::hasColumn('sections', 'name') ? 'name' : 'id');
            $sections = DB::table('sections')->orderBy($sortCol, 'asc')->get();
        }

        // 3. Get Class Schedules for the Authenticated Teacher
        $schedCols = Schema::hasTable('class_schedules') ? Schema::getColumnListing('class_schedules') : [];
        $teacherCol = in_array('teacher_id', $schedCols) ? 'teacher_id' : (in_array('user_id', $schedCols) ? 'user_id' : null);

        $mySchedules = collect([]);
        if (Schema::hasTable('class_schedules') && $teacherCol) {
            $mySchedules = ClassSchedule::with(['subjectRecord', 'academicSection'])
                ->where($teacherCol, $teacher->id)
                ->orderBy('start_time', 'asc')
                ->get();
        }

        $allSectionSchedules = collect();
        if (Schema::hasTable('class_schedules')) {
            $allSectionSchedules = ClassSchedule::with(['subjectRecord', 'teacher', 'academicSection'])
                ->orderBy('day')
                ->orderBy('start_time')
                ->get()
                ->map(function ($schedule) use ($teacher) {
                    $sectionName = optional($schedule->academicSection)->section_name ?: $schedule->section;
                    return [
                        'id' => $schedule->id,
                        'teacher_id' => (int) $schedule->teacher_id,
                        'section_id' => $schedule->section_id,
                        'section_name' => $sectionName,
                        'day' => trim($schedule->day ?? $schedule->day_of_week ?? 'Schedule'),
                        'start_time' => $schedule->start_time ? Carbon::parse($schedule->start_time)->format('g:i A') : 'TBA',
                        'end_time' => $schedule->end_time ? Carbon::parse($schedule->end_time)->format('g:i A') : 'TBA',
                        'start_time_value' => $schedule->start_time,
                        'end_time_value' => $schedule->end_time,
                        'subject' => $schedule->subject_name ?: optional($schedule->subjectRecord)->name ?: $schedule->subject ?: 'Class Schedule',
                        'subject_code' => $schedule->subject_code ?: optional($schedule->subjectRecord)->code,
                        'grade_level' => optional($schedule->academicSection)->grade_level ?: $schedule->grade_level,
                        'strand' => optional($schedule->academicSection)->strand ?: $schedule->strand,
                        'teacher' => optional($schedule->teacher)->first_name
                            ? trim(optional($schedule->teacher)->first_name . ' ' . optional($schedule->teacher)->last_name)
                            : 'Assigned Faculty',
                        'is_mine' => (int) $schedule->teacher_id === (int) $teacher->id,
                    ];
                });
        }

        $sections->each(function ($section) use ($allSectionSchedules) {
            $sectionName = trim((string) ($section->section_name ?? $section->name ?? ''));
            $section->schedule_list = $allSectionSchedules->filter(function ($schedule) use ($section, $sectionName) {
                return ($schedule['section_id'] && (int) $schedule['section_id'] === (int) $section->id)
                    || (!$schedule['section_id'] && $schedule['section_name'] === $sectionName);
            })->values();
            $section->has_my_schedule = $section->schedule_list->contains('is_mine', true);
        });

        // 4. Extract Teacher's unique assigned sections (for section filter)
        $teacherSectionNames = $mySchedules->map(function($sched) {
            return optional($sched->academicSection)->section_name ?: $sched->section;
        })->filter()->unique()->values();

        if (Schema::hasTable('academic_sections')) {
            $advisoryNames = DB::table('academic_sections')
                ->where('advisor_id', $teacher->id)
                ->pluck('section_name')
                ->all();
            $teacherSectionNames = $teacherSectionNames->merge($advisoryNames)->unique()->values();
        }

        $teacherSectionNames = $teacherSectionNames->filter()->values();
        $sections = $sections->filter(function ($section) use ($teacherSectionNames) {
            $sectionName = trim((string) ($section->section_name ?? $section->name ?? ''));
            return $teacherSectionNames->contains(function ($assignedSection) use ($sectionName) {
                return strcasecmp(trim((string) $assignedSection), $sectionName) === 0;
            });
        })->values();

        // Calculate student counts per section
        $sectionStudentCounts = [];
        if (Schema::hasTable('users')) {
            $sectionStudentCounts = DB::table('users')
                ->where('role_id', 3)
                ->whereNotNull('section')
                ->groupBy('section')
                ->select('section', DB::raw('count(*) as count'))
                ->pluck('count', 'section')
                ->all();
        }

        $teacherTimetableSchedules = $allSectionSchedules
            ->filter(function ($schedule) use ($teacherSectionNames) {
                return $teacherSectionNames->contains(function ($sectionName) use ($schedule) {
                    return strcasecmp(trim((string) $sectionName), trim((string) $schedule['section_name'])) === 0;
                });
            })
            ->map(function ($schedule) use ($sectionStudentCounts) {
                $schedule['student_count'] = $sectionStudentCounts[$schedule['section_name']] ?? 0;
                return $schedule;
            })
            ->values();

        // Attach student counts and normalized section name to schedules
        $mySchedules->each(function($sched) use ($sectionStudentCounts) {
            $sec = optional($sched->academicSection)->section_name ?: $sched->section;
            $sched->normalized_section = $sec;
            $sched->student_count = $sec && isset($sectionStudentCounts[$sec]) ? $sectionStudentCounts[$sec] : 0;
        });

        // Metrics for count cards
        $totalLoads = $mySchedules->count();
        $sectionsCount = $teacherSectionNames->count();
        $subjectsCount = $mySchedules->pluck('subject_name')->filter()->unique()->count();
        if ($subjectsCount === 0 && $mySchedules->count() > 0) {
            $subjectsCount = $mySchedules->pluck('subject')->filter()->unique()->count() ?: $totalLoads;
        }

        return view('teacher.classes', compact(
            'teacher',
            'mySchedules',
            'teacherTimetableSchedules',
            'activeSchoolYear',
            'schoolYears',
            'sections',
            'teacherSectionNames',
            'totalLoads',
            'sectionsCount',
            'subjectsCount',
            'search',
            'selectedSection',
            'viewMode'
        ));
    }

    public function schedule(Request $request)
    {
        return $this->classes($request);
    }

    public function index()
    {
        $teacher = Auth::user();
        $activePeriod = Schema::hasTable('academic_periods')
            ? DB::table('academic_periods')->where('is_active', 1)->first()
            : null;
        $schoolYearStart = $activePeriod?->start_date ? Carbon::parse($activePeriod->start_date)->startOfDay() : now()->startOfYear();
        $schoolYearEnd = $activePeriod?->end_date ? Carbon::parse($activePeriod->end_date)->endOfDay() : now()->endOfYear();
        $today = Carbon::today()->between($schoolYearStart, $schoolYearEnd) ? Carbon::today() : $schoolYearStart->copy()->startOfDay();
        $range = in_array(request('range', 'daily'), ['daily', 'weekly', 'monthly'], true) ? request('range', 'daily') : 'daily';
        $selectedDate = $this->dashboardDate(request('date'), $schoolYearStart, $schoolYearEnd, $today);
        [$rangeStart, $rangeEnd] = $this->dashboardRange($range, $selectedDate);

        $advisorySections = Schema::hasTable('academic_sections')
            ? DB::table('academic_sections')->where('advisor_id', $teacher->id)->get()
            : collect();
        $schedules = Schema::hasTable('class_schedules')
            ? ClassSchedule::with(['academicSection', 'subjectRecord'])->where('teacher_id', $teacher->id)->orderBy('start_time')->get()
            : collect();
        $sectionIds = $advisorySections->pluck('id')->filter()->values();
        $scheduleSectionIds = $schedules->pluck('section_id')->filter()->values();
        $sectionNames = $schedules->map(fn ($schedule) => $schedule->academicSection?->section_name ?: $schedule->section)->filter()->unique()->values();
        $students = User::where('role_id', 3)->where(function ($query) use ($sectionNames, $sectionIds, $scheduleSectionIds) {
            if ($sectionNames->isNotEmpty()) {
                $query->whereIn('section', $sectionNames);
            }
            $pivotSectionIds = $sectionIds->merge($scheduleSectionIds)->unique()->values();
            if ($pivotSectionIds->isNotEmpty() && Schema::hasTable('section_student')) {
                $query->orWhereIn('id', DB::table('section_student')->whereIn('section_id', $pivotSectionIds)->pluck('student_id'));
            }
        })->orderBy('last_name')->orderBy('first_name')->get();

        $studentIds = $students->pluck('id');
        $advisoryCount = $advisorySections->count();
        $subjectCount = $schedules->count();
        $attendanceQuery = $this->teacherAttendanceQuery($teacher, $studentIds, $schedules, $sectionIds);
        $rangeLogs = (clone $attendanceQuery)->whereBetween('attendance_logs.attendance_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])->get();
        $todayLogs = $range === 'daily' ? $rangeLogs : (clone $attendanceQuery)->whereDate('attendance_logs.attendance_date', $selectedDate)->get();
        $presentStatuses = ['ON-TIME', 'PRESENT', 'on-time', 'present'];
        $lateStatuses = ['LATE', 'late'];
        $presentToday = $todayLogs->whereIn('status', array_merge($presentStatuses, $lateStatuses))->pluck('user_id')->unique()->count();
        $presentRecords = $rangeLogs->whereIn('status', array_merge($presentStatuses, $lateStatuses))->count();
        $lateRecords = $rangeLogs->whereIn('status', $lateStatuses)->count();
        $totalRecords = $rangeLogs->count();
        $totalStudents = $students->count();
        $attendanceRate = $totalStudents > 0 ? round(($presentToday / $totalStudents) * 100, 1) : 0;
        $avgAttendanceRate = $totalRecords > 0 ? round(($presentRecords / $totalRecords) * 100, 1) : 0;
        $chartDates = [];
        $chartPresents = [];
        $chartLates = [];
        $chartAbsents = [];
        $chartAdvisoryRates = [];
        $chartSubjectRates = [];

        // Precompute advisory and subject student groups for date loop
        $advisoryStudentIds = $students->filter(fn ($s) => $advisorySections->contains('section_name', $s->section))->pluck('id');
        $totalAdvisoryStudents = $advisoryStudentIds->count();

        // Subject class schedules student IDs
        $subjectStudentIds = $students->filter(fn ($s) => $schedules->contains(fn ($sched) => ($sched->academicSection?->section_name ?: $sched->section) === $s->section))->pluck('id');
        $totalSubjectStudents = $subjectStudentIds->count();

        for ($cursor = $rangeStart->copy(); $cursor->lte($rangeEnd); $cursor->addDay()) {
            $key = $cursor->toDateString();
            $dayLogs = $rangeLogs->filter(fn ($log) => (string) $log->attendance_date === $key);
            $present = $dayLogs->whereIn('status', array_merge($presentStatuses, $lateStatuses))->pluck('user_id')->unique()->count();
            $chartDates[] = $range === 'monthly' ? $cursor->format('M d') : $cursor->format('D, M d');
            $chartPresents[] = $present;
            $chartLates[] = $dayLogs->whereIn('status', $lateStatuses)->pluck('user_id')->unique()->count();
            $chartAbsents[] = max(0, $totalStudents - $present);

            // Advisory attendance rate for this date
            if ($totalAdvisoryStudents > 0) {
                $dayAdvisoryPresent = $dayLogs->whereIn('user_id', $advisoryStudentIds)
                    ->whereIn('status', array_merge($presentStatuses, $lateStatuses))
                    ->pluck('user_id')->unique()->count();
                $chartAdvisoryRates[] = round(($dayAdvisoryPresent / $totalAdvisoryStudents) * 100, 1);
            } else {
                $chartAdvisoryRates[] = 0;
            }

            // Subject classes attendance rate for this date
            if ($totalSubjectStudents > 0) {
                $daySubjectPresent = $dayLogs->whereIn('user_id', $subjectStudentIds)
                    ->whereIn('status', array_merge($presentStatuses, $lateStatuses))
                    ->pluck('user_id')->unique()->count();
                $chartSubjectRates[] = round(($daySubjectPresent / $totalSubjectStudents) * 100, 1);
            } else {
                $chartSubjectRates[] = 0;
            }
        }

        $sectionStudentIds = Schema::hasTable('section_student')
            ? DB::table('section_student')->get(['section_id', 'student_id'])->groupBy('section_id')->map(
                fn ($rows) => $rows->pluck('student_id')->map(fn ($id) => (int) $id)->values()
            )
            : collect();
        $studentsInSection = function (?int $sectionId, ?string $sectionName) use ($students, $sectionStudentIds) {
            $sectionIds = $sectionId ? $sectionStudentIds->get($sectionId, collect()) : collect();
            return $students->filter(fn ($student) => $sectionIds->contains((int) $student->id)
                || ($sectionName && strcasecmp(trim((string) $student->section), trim($sectionName)) === 0))
                ->unique('id')
                ->values();
        };
        $subjectClassGroups = $schedules->groupBy(function ($schedule) use ($advisorySections): string {
            $sectionId = $schedule->section_id ?: $schedule->academicSection?->id;
            $sectionName = trim((string) ($schedule->academicSection?->section_name ?: $schedule->section));
            if (!$sectionId) {
                $sectionId = $advisorySections
                    ->first(fn ($section) => strcasecmp(trim((string) $section->section_name), $sectionName) === 0)
                    ?->id;
            }
            return $sectionId ? 'section_' . $sectionId : 'name_' . mb_strtolower($sectionName);
        });
        foreach ($advisorySections as $section) {
            $key = 'section_' . $section->id;
            if (!$subjectClassGroups->has($key)) {
                $subjectClassGroups->put($key, collect());
            }
        }

        $subjectSummary = $subjectClassGroups->map(function ($sectionSchedules, string $key) use ($studentsInSection, $rangeLogs, $advisorySections): array {
            $schedule = $sectionSchedules->first();
            $sectionId = $schedule?->section_id ?: $schedule?->academicSection?->id;
            $advisorySection = $sectionId
                ? $advisorySections->firstWhere('id', $sectionId)
                : null;
            if (!$advisorySection && str_starts_with($key, 'section_')) {
                $advisorySection = $advisorySections->firstWhere('id', (int) substr($key, strlen('section_')));
            }
            $section = $schedule?->academicSection?->section_name
                ?: $schedule?->section
                ?: $advisorySection?->section_name
                ?: 'Unassigned';
            $sectionId = $sectionId ?: $advisorySection?->id;
            $sectionStudents = $studentsInSection($sectionId, $section);

            $subjectNames = $sectionSchedules
                ->map(fn ($class) => trim((string) ($class->subjectRecord?->name ?: $class->subject_name ?: $class->subject_code ?: '')))
                ->filter()
                ->unique(fn ($name) => mb_strtolower($name))
                ->values();
            $isAdvisory = $advisorySection !== null
                || $advisorySections->contains(fn ($candidate) => strcasecmp(trim((string) $candidate->section_name), trim($section)) === 0);
            $sectionLabel = $section . ($isAdvisory ? ' (Advisory)' : '');
            $scheduleIds = $sectionSchedules->pluck('id');
            $sectionLogs = $rangeLogs->whereIn('class_schedule_id', $scheduleIds);

            $maleCount = $sectionStudents->filter(fn ($s) => in_array(strtolower((string)$s->gender), ['1', 'm', 'male']))->count();
            $femaleCount = $sectionStudents->filter(fn ($s) => in_array(strtolower((string)$s->gender), ['2', 'f', 'female']))->count();
            $present = $sectionLogs->whereIn('status', ['ON-TIME', 'PRESENT', 'LATE', 'on-time', 'present', 'late'])->pluck('user_id')->unique()->count();
            return [
                'label' => $subjectNames->isNotEmpty() ? [$sectionLabel, $subjectNames->implode(' / ')] : $sectionLabel,
                'full_label' => $sectionLabel . ($subjectNames->isNotEmpty() ? ' - ' . $subjectNames->implode(' / ') : ''),
                'subject_name' => $subjectNames->implode(' / '),
                'section_name' => $section,
                'is_advisory' => $isAdvisory,
                'students' => $sectionStudents->count(),
                'present' => $present,
                'male_count' => $maleCount,
                'female_count' => $femaleCount,
            ];
        })->values();

        // Gender breakdown per advisory section
        $advisorySummary = $advisorySections->map(function ($section) use ($studentsInSection, $rangeLogs): array {
            $sectionStudents = $studentsInSection((int) $section->id, $section->section_name);
            $maleCount = $sectionStudents->filter(fn ($s) => in_array(strtolower((string)$s->gender), ['1', 'm', 'male']))->count();
            $femaleCount = $sectionStudents->filter(fn ($s) => in_array(strtolower((string)$s->gender), ['2', 'f', 'female']))->count();

            $present = $rangeLogs->where('advisory_section_id', $section->id)->whereIn('status', ['ON-TIME', 'PRESENT', 'LATE', 'on-time', 'present', 'late'])->pluck('user_id')->unique()->count();
            return [
                'label' => $section->section_name,
                'section_id' => $section->id,
                'students' => $sectionStudents->count(),
                'present' => $present,
                'male_count' => $maleCount,
                'female_count' => $femaleCount,
            ];
        })->values();

        $classAttendanceRate = $subjectSummary->sum('students') > 0
            ? round(($subjectSummary->sum('present') / $subjectSummary->sum('students')) * 100, 1)
            : 0;
        $advisoryAttendanceRate = $advisorySummary->sum('students') > 0
            ? round(($advisorySummary->sum('present') / $advisorySummary->sum('students')) * 100, 1)
            : 0;

        // Check if an evaluation cycle is currently active
        $activeEvaluationCycle = null;
        if (Schema::hasTable('evaluation_cycles')) {
            $activeEvaluationCycle = DB::table('evaluation_cycles')
                ->where('is_active', 1)
                ->where('status', 'active')
                ->first();
        }

        $isEvaluationActive = $activeEvaluationCycle !== null && Schema::hasTable('evaluation_submissions');

        // Teacher's own evaluation status (self-evaluation and peer evaluations)
        $selfEvaluationDone = false;
        $peerEvaluationDone = false;
        $allTeacherEvaluationsDone = true;

        if ($isEvaluationActive && $activeEvaluationCycle) {
            if (Schema::hasTable('self_evaluations')) {
                $selfQuery = DB::table('self_evaluations')->where('teacher_id', $teacher->id);
                if (Schema::hasColumn('self_evaluations', 'evaluation_cycle_id')) {
                    $selfQuery->where('evaluation_cycle_id', $activeEvaluationCycle->id);
                }
                $selfEvaluationDone = $selfQuery->exists();
            }

            if (Schema::hasTable('peer_evaluations')) {
                $peerEvaluationDone = DB::table('peer_evaluations')
                    ->where('evaluator_id', $teacher->id)
                    ->where('evaluation_cycle_id', $activeEvaluationCycle->id)
                    ->exists();
            }

            $allTeacherEvaluationsDone = $selfEvaluationDone && $peerEvaluationDone;
        }

        // Attendance by Advisory: Male / Female stacked data
        $advisoryFemaleCounts = $advisorySummary->pluck('female_count')->values();
        $advisoryMaleCounts = $advisorySummary->pluck('male_count')->values();

        // Attendance by Subject: Male / Female stacked data
        $subjectFemaleCounts = $subjectSummary->pluck('female_count')->values();
        $subjectMaleCounts = $subjectSummary->pluck('male_count')->values();

        // Students by Grade: Grouping teacher's students by Grade Level with Male/Female counts
        $gradeGrouped = $students->groupBy(function ($s) {
            $g = trim((string)($s->grade_level ?? ''));
            return $g !== '' ? (str_starts_with(strtolower($g), 'grade') ? $g : 'Grade ' . $g) : 'Unassigned';
        })->sortKeys();

        $gradeLabels = $gradeGrouped->keys()->values();
        $gradeFemaleCounts = $gradeGrouped->map(fn ($group) => $group->filter(fn ($s) => in_array(strtolower((string)$s->gender), ['2', 'f', 'female']))->count())->values();
        $gradeMaleCounts = $gradeGrouped->map(fn ($group) => $group->filter(fn ($s) => in_array(strtolower((string)$s->gender), ['1', 'm', 'male']))->count())->values();

        // Return JSON if requested via AJAX
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'range' => $range,
                'selectedDate' => $selectedDate->toDateString(),
                'rangeStartFormatted' => $rangeStart->format('M d, Y'),
                'rangeEndFormatted' => $rangeEnd->format('M d, Y'),
                'chartDates' => $chartDates,
                'chartPresents' => $chartPresents,
                'chartLates' => $chartLates,
                'chartAbsents' => $chartAbsents,
                'chartAdvisoryRates' => $chartAdvisoryRates,
                'chartSubjectRates' => $chartSubjectRates,
                'advisoryLabels' => $advisorySummary->pluck('label'),
                'advisoryPresent' => $advisorySummary->pluck('present'),
                'advisorySummary' => $advisorySummary,
                'advisoryFemaleCounts' => $advisoryFemaleCounts,
                'advisoryMaleCounts' => $advisoryMaleCounts,
                'subjectLabels' => $subjectSummary->pluck('label'),
                'subjectStudents' => $subjectSummary->pluck('students'),
                'subjectFemaleCounts' => $subjectFemaleCounts,
                'subjectMaleCounts' => $subjectMaleCounts,
                'subjectSummary' => $subjectSummary,
                'classAttendanceRate' => $classAttendanceRate,
                'advisoryAttendanceRate' => $advisoryAttendanceRate,
                'presentToday' => $presentToday,
                'attendanceRate' => $attendanceRate,
                'avgAttendanceRate' => $avgAttendanceRate,
                'selfEvaluationDone' => $selfEvaluationDone,
                'peerEvaluationDone' => $peerEvaluationDone,
                'allTeacherEvaluationsDone' => $allTeacherEvaluationsDone,
            ]);
        }

        return view('teacher.dashboard', compact(
            'teacher', 'activePeriod', 'schoolYearStart', 'schoolYearEnd', 'range', 'selectedDate', 'rangeStart', 'rangeEnd',
            'totalStudents', 'advisoryCount', 'subjectCount', 'presentToday', 'attendanceRate', 'classAttendanceRate', 'advisoryAttendanceRate', 'totalRecords',
            'presentRecords', 'lateRecords', 'avgAttendanceRate', 'chartDates', 'chartPresents', 'chartLates', 'chartAbsents',
            'chartAdvisoryRates', 'chartSubjectRates',
            'subjectSummary', 'advisorySummary',
            'isEvaluationActive', 'activeEvaluationCycle',
            'selfEvaluationDone', 'peerEvaluationDone', 'allTeacherEvaluationsDone',
            'advisoryFemaleCounts', 'advisoryMaleCounts', 'subjectFemaleCounts', 'subjectMaleCounts',
            'gradeLabels', 'gradeFemaleCounts', 'gradeMaleCounts'
        ));
    }

    private function dashboardDate(?string $value, Carbon $start, Carbon $end, Carbon $fallback): Carbon
    {
        try {
            $date = Carbon::parse($value ?: $fallback->toDateString())->startOfDay();
        } catch (\Throwable) {
            $date = $fallback->copy()->startOfDay();
        }
        return $date->between($start, $end) ? $date : $fallback->copy()->startOfDay();
    }

    private function dashboardRange(string $range, Carbon $date): array
    {
        $start = match ($range) {
            'weekly' => $date->copy()->startOfWeek(Carbon::MONDAY),
            'monthly' => $date->copy()->startOfMonth(),
            default => $date->copy()->startOfDay(),
        };
        $end = match ($range) {
            'weekly' => $date->copy()->endOfWeek(Carbon::SUNDAY),
            'monthly' => $date->copy()->endOfMonth(),
            default => $date->copy()->endOfDay(),
        };
        return [$start, $end];
    }

    private function teacherAttendanceQuery($teacher, $studentIds, $schedules, $sectionIds)
    {
        $query = DB::table('attendance_logs')->whereIn('attendance_logs.user_id', $studentIds->isNotEmpty() ? $studentIds : [0]);
        if (Schema::hasColumn('attendance_logs', 'attendance_date')) {
            $query->whereNotNull('attendance_logs.attendance_date');
        }
        $query->where(function ($scope) use ($teacher, $schedules, $sectionIds) {
            if ($schedules->isNotEmpty() && Schema::hasColumn('attendance_logs', 'class_schedule_id')) {
                $scope->whereIn('attendance_logs.class_schedule_id', $schedules->pluck('id'));
            }
            if ($sectionIds->isNotEmpty() && Schema::hasColumn('attendance_logs', 'advisory_section_id')) {
                $scope->orWhereIn('attendance_logs.advisory_section_id', $sectionIds);
            }
        });
        return $query->select('attendance_logs.*');
    }

    public function updateProfile(Request $request)
    {
        $teacher = Auth::user();

        $request->validate([
            'first_name'   => ['required', 'string', 'max:255'],
            'last_name'    => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email,' . $teacher->id],
            'id_number'    => ['required', 'string', 'max:255'],
            'gender'       => ['required', 'in:1,2'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'password'     => ['nullable', 'string', 'min:8', 'confirmed'],
            'photo'        => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $teacher->first_name   = $request->first_name;
        $teacher->last_name    = $request->last_name;
        $teacher->email        = $request->email;
        $teacher->id_number    = $request->id_number;
        $teacher->gender       = $request->gender;
        $teacher->phone_number = $request->phone_number;

        if ($request->filled('password')) {
            $teacher->password = Hash::make($request->password);
        }

        if ($request->hasFile('photo')) {
            if ($teacher->photo && Storage::disk('public')->exists($teacher->photo)) {
                Storage::disk('public')->delete($teacher->photo);
            }
            $path = $request->file('photo')->store('teacher-photos', 'public');
            $teacher->photo = $path;
        }

        $teacher->save();

        AuditLog::record(
            action: 'Updated Faculty Profile',
            module: 'Faculty',
            status: 'Success',
            description: "Faculty {$teacher->first_name} {$teacher->last_name} updated their profile."
        );

        return redirect()->back()->with('success', 'Faculty profile updated successfully!');
    }

    public function schoolYears(Request $request)
    {
        return $this->classes($request);
    }

    public function students(Request $request)
    {
        $teacher = Auth::user();
        $search = trim((string) $request->query('search'));
        $sortOrder = $request->query('sort', 'asc');
        $tabs = collect();

        $advisorySectionIds = [];
        $advisorySectionNames = [];
        $advisorySections = collect();
        if (Schema::hasTable('academic_sections')) {
            $sections = DB::table('academic_sections')->where('advisor_id', $teacher->id)->get();
            $advisorySections = $sections;
            $advisorySectionIds = $sections->pluck('id')->all();
            $advisorySectionNames = $sections->pluck('section_name')->all();
            foreach ($sections as $section) {
                $tabs->prepend([
                    'id' => 'advisory-' . $section->id,
                    'type' => 'advisory',
                    'label' => 'Advisory - ' . ($section->grade_level ? 'Grade ' . $section->grade_level . ' ' : '') . $section->section_name,
                    'subject' => 'Advisory',
                    'grade' => $section->grade_level,
                    'section' => $section->section_name,
                    'section_id' => $section->id,
                    'students' => collect(),
                ]);
            }
        }

        if (Schema::hasTable('class_schedules')) {
            $teacherColumn = Schema::hasColumn('class_schedules', 'teacher_id') ? 'teacher_id' : (Schema::hasColumn('class_schedules', 'user_id') ? 'user_id' : null);
            if ($teacherColumn) {
                foreach (ClassSchedule::with(['subjectRecord', 'academicSection'])->where($teacherColumn, $teacher->id)->get() as $schedule) {
                    $section = optional($schedule->academicSection)->section_name ?: $schedule->section;
                    if (!$section) continue;
                    $grade = optional($schedule->academicSection)->grade_level ?: $schedule->grade_level;
                    $subject = $schedule->subject_name ?: optional($schedule->subjectRecord)->name ?: $schedule->subject ?: 'Subject';
                    $tabs->push([
                        'id' => 'subject-' . $schedule->id,
                        'type' => 'subject',
                        'label' => trim($subject . ' - ' . ($grade ? 'Grade ' . $grade . ' ' : '') . $section),
                        'subject' => $subject,
                        'grade' => $grade,
                        'section' => $section,
                        'section_id' => $schedule->section_id,
                        'schedule_id' => $schedule->id,
                        'students' => collect(),
                    ]);
                }
            }
        }

        $tabs = $tabs->unique(fn ($tab) => $tab['type'] . '|' . $tab['subject'] . '|' . $tab['grade'] . '|' . $tab['section'])->values();

        $activeEvaluationCycle = Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')->where('status', 'active')->where('is_active', 1)->first()
            : null;
        $evaluatedStudentIds = ($activeEvaluationCycle && Schema::hasTable('peer_evaluations'))
            ? DB::table('peer_evaluations')
                ->where('evaluatee_id', $teacher->id)
                ->where('evaluation_cycle_id', $activeEvaluationCycle->id)
                ->pluck('evaluator_id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        $advisoryStudentIds = collect();
        if (Schema::hasTable('section_student') && !empty($advisorySectionIds)) {
            $advisoryStudentIds = DB::table('section_student')
                ->whereIn('section_id', $advisorySectionIds)
                ->pluck('student_id');
        }

        $baseQuery = User::with('nfcCard')->where('role_id', 3);
        if ($search) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $attendanceDate = $request->query('date', today()->toDateString());
        $tabs = $tabs->map(function ($tab) use ($baseQuery, $sortOrder, $advisorySectionNames, $advisoryStudentIds, $teacher, $evaluatedStudentIds, $activeEvaluationCycle, $attendanceDate) {
            $query = clone $baseQuery;
            $students = $query->where('section', $tab['section'])->orderBy('last_name', $sortOrder === 'desc' ? 'desc' : 'asc')->get();

            $attendanceQuery = collect();
            if (Schema::hasTable('attendance_logs') && $students->isNotEmpty()) {
                $attendanceStudentColumn = Schema::hasColumn('attendance_logs', 'student_id') ? 'student_id' : 'user_id';
                $attendanceDateColumn = Schema::hasColumn('attendance_logs', 'attendance_date')
                    ? 'attendance_date'
                    : (Schema::hasColumn('attendance_logs', 'scanned_at') ? 'scanned_at' : 'created_at');
                $attendanceBuilder = DB::table('attendance_logs');
                $attendanceBuilder->whereIn('attendance_logs.' . $attendanceStudentColumn, $students->pluck('id'));
                if (Schema::hasColumn('attendance_logs', 'attendance_type')) {
                    $attendanceBuilder->where('attendance_logs.attendance_type', $tab['type']);
                }
                if ($tab['type'] === 'subject' && !empty($tab['schedule_id'])) {
                    $attendanceBuilder->where('attendance_logs.class_schedule_id', $tab['schedule_id']);
                }
                if ($tab['type'] === 'advisory' && !empty($tab['section_id'])) {
                    $attendanceBuilder->where('attendance_logs.advisory_section_id', $tab['section_id']);
                }
                $attendanceQuery = $attendanceBuilder
                    ->whereDate('attendance_logs.' . $attendanceDateColumn, $attendanceDate)
                    ->select(
                        'attendance_logs.' . $attendanceStudentColumn . ' as student_id',
                        'attendance_logs.' . $attendanceDateColumn . ' as attendance_date',
                        'attendance_logs.time_in', 'attendance_logs.time_out',
                        'attendance_logs.status'
                    )
                    ->latest('attendance_logs.id')
                    ->get()
                    ->groupBy('student_id');
            }

            $students->each(function ($student) use ($advisorySectionNames, $advisoryStudentIds) {
                $isAdvisory = in_array($student->section, $advisorySectionNames)
                    || $advisoryStudentIds->contains($student->id);
                $student->can_edit = (bool) $isAdvisory;
            });

            $students->each(function ($student) use ($attendanceQuery, $evaluatedStudentIds, $activeEvaluationCycle) {
                $attendance = $attendanceQuery->get($student->id, collect())->first();
                $student->attendance_date = $attendance?->attendance_date;
                $student->attendance_time_in = $attendance?->time_in;
                $student->attendance_time_out = $attendance?->time_out;
                $student->attendance_status = $attendance
                    ? 'Present'
                    : 'Absent';
                $student->evaluation_status = $activeEvaluationCycle
                    ? (in_array((int) $student->id, $evaluatedStudentIds, true) ? 'Done' : 'Not Evaluated')
                    : 'Not Active';
            });

            $tab['students'] = $students;
            return $tab;
        });

        $students = $tabs->flatMap(fn ($tab) => $tab['students'])->unique('id')->values();
        $totalStudents = $students->count();

        $advisoryTabs = $tabs->where('type', 'advisory');
        $subjectTabs = $tabs->where('type', 'subject');
        $advisoryCount = $advisoryTabs->flatMap(fn ($tab) => $tab['students'])->unique('id')->count();
        $subjectCount = $subjectTabs->flatMap(fn ($tab) => $tab['students'])->unique('id')->count();
        $boundCount = $students->filter(fn ($student) => !empty(optional($student->nfcCard)->tag_id))->count();
        $unboundCount = max(0, $totalStudents - $boundCount);

        return view('teacher.students', compact(
            'teacher',
            'students',
            'tabs',
            'totalStudents',
            'advisoryCount',
            'subjectCount',
            'boundCount',
            'unboundCount',
            'search',
            'sortOrder'
            , 'advisorySections'
        ));
    }

    public function updateStudent(Request $request, $id)
    {
        $student = User::where('role_id', 3)->findOrFail($id);
        $teacher = Auth::user();

        $canUpdate = false;
        if (Schema::hasTable('academic_sections')) {
            $advisorSections = DB::table('academic_sections')
                ->where('advisor_id', $teacher->id)
                ->get();
            $sectionNames = $advisorSections->pluck('section_name')->all();
            $sectionIds = $advisorSections->pluck('id')->all();

            $isEnrolledInAdvisorySection = false;
            if (Schema::hasTable('section_student') && !empty($sectionIds)) {
                $isEnrolledInAdvisorySection = DB::table('section_student')
                    ->where('student_id', $student->id)
                    ->whereIn('section_id', $sectionIds)
                    ->exists();
            }

            if (in_array($student->section, $sectionNames) || $isEnrolledInAdvisorySection) {
                $canUpdate = true;
            }
        }

        abort_unless($canUpdate, 403, 'Only the student’s advisory teacher can update this record.');
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'], 'middle_name' => ['nullable', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:255'],
            'id_number' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('users', 'id_number')->ignore($student->id)],
            'phone_number' => ['required', 'string', 'max:20'], 'gender' => ['required', 'string', 'max:20'], 'grade_level' => ['required', 'string', 'max:20'],
            'strand' => ['nullable', 'string', 'max:100'], 'section' => ['required', 'string', 'max:255'], 'parent_name' => ['required', 'string', 'max:255'],
            'parent_relationship' => ['required', 'string', 'max:50'], 'parent_phone_number' => ['required', 'string', 'max:20'],
        ]);
        $gradeNumber = preg_replace('/\D/', '', (string) $request->grade_level);
        $sectionCandidates = $advisorSections->filter(fn ($section) =>
            $section->section_name === $request->section
            && preg_replace('/\D/', '', (string) $section->grade_level) === $gradeNumber
        );
        $targetSection = $sectionCandidates->first(fn ($section) =>
            strtoupper(trim((string) $section->strand)) === strtoupper(trim((string) $request->strand))
        ) ?? $sectionCandidates->first();
        abort_unless($targetSection, 403, 'You can only assign students to your advisory sections.');

        foreach (['first_name', 'middle_name', 'last_name', 'id_number', 'phone_number', 'gender', 'parent_name', 'parent_relationship', 'parent_phone_number'] as $field) {
            if (Schema::hasColumn('users', $field)) $student->{$field} = $request->input($field);
        }
        $student->username = $this->generatedStudentUsername($request->id_number, $request->first_name, $student->id);
        $student->grade_level = preg_replace('/\D/', '', (string) $targetSection->grade_level);
        $student->strand = $targetSection->strand ?: null;
        $student->section = $targetSection->section_name;
        $student->save();

        if (Schema::hasTable('section_student')) {
            DB::table('section_student')->where('student_id', $student->id)->whereIn('section_id', $sectionIds)->delete();
            DB::table('section_student')->insertOrIgnore([
                'section_id' => $targetSection->id,
                'student_id' => $student->id,
            ]);
        }

        AuditLog::record(
            action: 'Updated Student Information',
            module: 'Students',
            status: 'Success',
            description: "Faculty {$teacher->first_name} {$teacher->last_name} updated student {$student->first_name} {$student->last_name} (LRN: {$student->id_number}, Section: {$student->section})."
        );

        return back()->with('success', 'Student information updated successfully.');
    }

    public function storeStudent(Request $request)
    {
        $teacher = Auth::user();
        abort_unless(Schema::hasTable('academic_sections'), 403, 'Academic sections are not configured.');

        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:255'],
            'id_number'  => ['required', 'string', 'max:255', 'unique:users,id_number'],
            'gender'     => ['required'],
            'grade_level' => ['required', 'string', 'max:20'],
            'strand'     => ['nullable', 'string', 'max:100'],
            'section'    => ['required', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'max:20'],
            'parent_name' => ['required', 'string', 'max:255'],
            'parent_relationship' => ['required', 'string', 'max:50'],
            'parent_phone_number' => ['required', 'string', 'max:20'],
            'password'   => ['nullable', 'string', 'min:6'],
        ]);

        $sectionCandidates = DB::table('academic_sections')
            ->where('advisor_id', $teacher->id)
            ->where('section_name', $request->section)
            ->get()
            ->filter(fn ($section) =>
                preg_replace('/\D/', '', (string) $section->grade_level)
                    === preg_replace('/\D/', '', (string) $request->grade_level)
            );
        $section = $sectionCandidates->first(fn ($section) =>
            strtoupper(trim((string) $section->strand)) === strtoupper(trim((string) $request->strand))
        ) ?? $sectionCandidates->first();
        abort_unless($section, 403, 'You can only add students to your assigned advisory section.');

        $student = User::create([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name'  => $request->last_name,
            'username' => $this->generatedStudentUsername($request->id_number, $request->first_name),
            'id_number'  => $request->id_number,
            'gender'     => $request->gender,
            'grade_level' => preg_replace('/\D/', '', (string) $section->grade_level),
            'strand'     => $section->strand ?: null,
            'section'    => $section->section_name,
            'phone_number' => $request->phone_number,
            'parent_name' => $request->parent_name,
            'parent_relationship' => $request->parent_relationship,
            'parent_phone_number' => $request->parent_phone_number,
            'role_id'    => 3, 
            'academic_period_id' => Schema::hasTable('academic_periods')
                ? DB::table('academic_periods')->where('is_active', 1)->value('id')
                : null,
            'password'   => Hash::make($request->password ?: 'password123'), 
        ]);

        if (Schema::hasTable('section_student')) {
            DB::table('section_student')->insertOrIgnore([
                'section_id' => $section->id,
                'student_id' => $student->id,
            ]);
        }

        AuditLog::record(
            action: 'Added Student',
            module: 'Students',
            status: 'Success',
            description: "Faculty {$teacher->first_name} {$teacher->last_name} enrolled new student {$student->first_name} {$student->last_name} (LRN: {$student->id_number}) into section {$section->section_name}."
        );

        return redirect()->back()->with('success', 'Matagumpay na naidagdag ang bagong estudyante!');
    }

    private function generatedStudentUsername(string $idNumber, string $firstName, ?int $ignoreId = null): string
    {
        $base = strtolower(
            preg_replace('/[^a-z0-9]/i', '', $idNumber)
            . preg_replace('/[^a-z0-9]/i', '', $firstName)
        );
        $base = substr($base !== '' ? $base : 'student', 0, 100);
        $username = $base;
        $suffix = 2;

        while (User::where('username', $username)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $suffixText = (string) $suffix++;
            $username = substr($base, 0, 100 - strlen($suffixText)) . $suffixText;
        }

        return $username;
    }

    public function reports(Request $request)
    {
        $teacher = Auth::user();
        $today = Carbon::today()->toDateString();
        $myClasses = ClassSchedule::with(['subjectRecord', 'academicSection'])
            ->where('teacher_id', $teacher->id)
            ->orderBy('section')
            ->orderBy('start_time')
            ->get();
        $advisorySections = Schema::hasTable('academic_sections')
            ? DB::table('academic_sections')
                ->where('advisor_id', $teacher->id)
                ->orderBy('grade_level')
                ->orderBy('section_name')
                ->get(['id', 'section_name', 'grade_level'])
            : collect();

        $period = $request->input('period', 'daily');
        validator(['period' => $period], [
            'period' => ['required', 'in:daily,weekly,monthly'],
        ])->validate();
        $fallbackDate = $request->input('report_date', now()->toDateString());
        $periodValue = $request->input('period_value');
        if (!$periodValue) {
            $fallback = Carbon::parse($fallbackDate);
            $periodValue = match ($period) {
                'weekly' => $fallback->format('o-\WW'),
                'monthly' => $fallback->format('Y-m'),
                default => $fallback->toDateString(),
            };
        }
        $request->merge([
            'report_by' => $request->input('report_by', 'section'),
            'period' => $period,
            'period_value' => $periodValue,
        ]);
        $filters = $request->validate([
            'report_by' => ['required', 'in:section,subject'],
            'period' => ['required', 'in:daily,weekly,monthly'],
            'period_value' => ['bail', 'required', 'string', function ($attribute, $value, $fail) use ($period): void {
                $valid = match ($period) {
                    'weekly' => preg_match('/^(\d{4})-W(\d{2})$/D', $value, $matches)
                        && (int) $matches[1] > 0
                        && Carbon::now()->setISODate((int) $matches[1], (int) $matches[2], Carbon::MONDAY)->format('o-W') === $matches[1] . '-' . $matches[2],
                    'monthly' => (bool) preg_match('/^[1-9]\d{3}-(0[1-9]|1[0-2])$/D', $value),
                    default => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)
                        && checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4)),
                };

                if (!$valid) {
                    $fail('Select a valid date, week, or month for the chosen report period.');
                }
            }],
            'section_id' => ['nullable', 'integer', 'exists:academic_sections,id'],
            'schedule_id' => ['nullable', 'integer', 'exists:class_schedules,id'],
            'status' => ['nullable', 'in:ON-TIME,LATE,ABSENT'],
            'per_page' => ['nullable', 'integer', 'in:10,20'],
        ]);

        $advisoryIds = $advisorySections->pluck('id');
        $scheduleIds = $myClasses->pluck('id');
        $selectionField = $filters['report_by'] === 'section' ? 'section_id' : 'schedule_id';
        $availableSelections = $filters['report_by'] === 'section' ? $advisorySections : $myClasses;
        $selectedId = $filters[$selectionField] ?? $availableSelections->first()?->id;
        $selectedItem = $availableSelections->firstWhere('id', (int) $selectedId);

        if ($selectedId && !$selectedItem) {
            abort(403, 'You may only view attendance for your assigned sections or class schedules.');
        }
        $filters['section_id'] = $filters['report_by'] === 'section' ? $selectedId : null;
        $filters['schedule_id'] = $filters['report_by'] === 'subject' ? $selectedId : null;
        $filters['status'] = $filters['status'] ?? null;

        $periodDate = match ($filters['period']) {
            'weekly' => (function () use ($periodValue): Carbon {
                preg_match('/^(\d{4})-W(\d{2})$/D', $periodValue, $matches);
                return Carbon::now()->setISODate((int) $matches[1], (int) $matches[2], Carbon::MONDAY)->startOfDay();
            })(),
            'monthly' => Carbon::createFromFormat('!Y-m', $periodValue)->startOfDay(),
            default => Carbon::parse($periodValue)->startOfDay(),
        };
        $filters['report_date'] = $periodDate->toDateString();
        $filters['period_value'] = $periodValue;
        if ($filters['period'] === 'weekly') {
            $periodStart = $periodDate->copy()->startOfWeek(Carbon::MONDAY);
            $periodEnd = $periodStart->copy()->endOfWeek(Carbon::SUNDAY);
        } elseif ($filters['period'] === 'monthly') {
            $periodStart = $periodDate->copy()->startOfMonth();
            $periodEnd = $periodDate->copy()->endOfMonth();
        } else {
            $periodStart = $periodDate->copy();
            $periodEnd = $periodDate->copy();
        }

        $dates = collect();
        for ($date = $periodStart->copy(); $date->lte($periodEnd); $date->addDay()) {
            $dates->push($date->copy());
        }
        $periodLabel = match ($filters['period']) {
            'weekly' => $periodStart->format('M j') . ' – ' . $periodEnd->format('M j, Y'),
            'monthly' => $periodStart->format('F Y'),
            default => $periodDate->format('F j, Y'),
        };

        $students = collect();
        if ($selectedItem) {
            $studentQuery = User::query()
                ->where('role_id', 3)
                ->select(['id', 'id_number', 'first_name', 'last_name', 'section']);

            $sectionId = $filters['report_by'] === 'section'
                ? $selectedItem->id
                : ($selectedItem->section_id ?? null);
            $legacySectionName = $filters['report_by'] === 'section'
                ? $selectedItem->section_name
                : ($selectedItem->academicSection?->section_name ?: $selectedItem->section);

            if ($sectionId && Schema::hasTable('section_student')) {
                $studentQuery->where(function ($scope) use ($sectionId, $legacySectionName, $selectedItem, $filters) {
                    if ($legacySectionName) {
                        $scope->where('section', $legacySectionName);
                    }
                    $scope->orWhereIn('users.id', function ($query) use ($sectionId) {
                        $query->select('student_id')->from('section_student')->where('section_id', $sectionId);
                    });
                    if (Schema::hasTable('attendance_logs')) {
                        $scope->orWhereIn('users.id', function ($query) use ($selectedItem, $filters) {
                            $query->select('user_id')
                                ->from('attendance_logs')
                                ->where('attendance_type', $filters['report_by'] === 'section' ? 'advisory' : 'subject')
                            ->where($filters['report_by'] === 'section' ? 'advisory_section_id' : 'class_schedule_id', $selectedItem->id);
                        });
                    }
                });
            } elseif ($legacySectionName) {
                $studentQuery->where(function ($scope) use ($legacySectionName, $selectedItem, $filters) {
                    $scope->where('section', $legacySectionName);
                    if ($filters['report_by'] === 'subject' && Schema::hasTable('attendance_logs')) {
                        $scope->orWhereIn('users.id', function ($query) use ($selectedItem) {
                            $query->select('user_id')
                                ->from('attendance_logs')
                                ->where('attendance_type', 'subject')
                                ->where('class_schedule_id', $selectedItem->id);
                        });
                    }
                });
            } else {
                $studentQuery->whereRaw('1 = 0');
            }

            $students = $studentQuery->orderBy('last_name')->orderBy('first_name')->get();
        }

        $logs = collect();
        if ($selectedItem) {
            $logsQuery = DB::table('attendance_logs')
                ->join('users', 'attendance_logs.user_id', '=', 'users.id')
                ->where('users.role_id', 3)
                ->where('attendance_logs.attendance_type', $filters['report_by'] === 'section' ? 'advisory' : 'subject')
                ->whereBetween('attendance_logs.attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);

            if ($filters['report_by'] === 'section') {
                $studentIds = $students->pluck('id');
                $logsQuery->where(function ($scope) use ($selectedItem, $studentIds) {
                    $scope->where('attendance_logs.advisory_section_id', $selectedItem->id)
                        ->orWhere(function ($legacy) use ($studentIds) {
                            $legacy->whereNull('attendance_logs.advisory_section_id')
                                ->whereIn('attendance_logs.user_id', $studentIds);
                        });
                });
            } else {
                $logsQuery->where('attendance_logs.class_schedule_id', $selectedItem->id);
            }
            if ($filters['status']) {
                $logsQuery->whereRaw('UPPER(attendance_logs.status) = ?', [$filters['status']]);
            }

            $logs = $logsQuery
                ->select([
                    'attendance_logs.user_id',
                    'attendance_logs.attendance_date',
                    'attendance_logs.time_in',
                    'attendance_logs.time_out',
                    'attendance_logs.status',
                ])
                ->orderBy('attendance_logs.id')
                ->get();
        }

        $logsByStudentDate = $logs->keyBy(
            fn ($log) => $log->user_id . '|' . Carbon::parse($log->attendance_date)->toDateString()
        );
        $reportRows = $students->map(function ($student) use ($dates, $logsByStudentDate) {
            $dailyLogs = [];
            foreach ($dates as $date) {
                $dailyLogs[$date->toDateString()] = $logsByStudentDate->get(
                    $student->id . '|' . $date->toDateString()
                );
            }

            return (object) [
                'student' => $student,
                'logs' => $dailyLogs,
            ];
        });
        if ($filters['status']) {
            $reportRows = $reportRows->filter(
                fn ($row) => collect($row->logs)->contains(
                    fn ($log) => $log && strtoupper((string) $log->status) === $filters['status']
                )
            )->values();
        }

        $summary = [
            'total' => $logs->count(),
            'present' => $logs->filter(fn ($log) => in_array(strtoupper((string) $log->status), ['ON-TIME', 'PRESENT'], true))->count(),
            'late' => $logs->filter(fn ($log) => strtoupper((string) $log->status) === 'LATE')->count(),
            'absent' => $logs->filter(fn ($log) => strtoupper((string) $log->status) === 'ABSENT')->count(),
        ];
        $perPage = (int) ($filters['per_page'] ?? 10);
        $page = max(1, (int) $request->input('page', 1));
        $attendanceRows = new \Illuminate\Pagination\LengthAwarePaginator(
            $reportRows->forPage($page, $perPage)->values(),
            $reportRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        $selectedLabel = $filters['report_by'] === 'section'
            ? ($selectedItem ? "Grade {$selectedItem->grade_level} · {$selectedItem->section_name}" : null)
            : ($selectedItem
                ? ($selectedItem->subject_name ?: $selectedItem->subjectRecord?->name ?: $selectedItem->subject_code ?: 'Subject')
                    . ' · ' . ($selectedItem->academicSection?->section_name ?: $selectedItem->section ?: 'Section')
                : null);

        if ($request->ajax()) {
            return view('teacher.partials.attendance-report-results', compact(
                'attendanceRows', 'filters', 'summary', 'dates', 'periodLabel', 'selectedLabel'
            ));
        }

        return view('teacher.reports', compact(
            'teacher', 'myClasses', 'advisorySections', 'attendanceRows', 'filters', 'summary',
            'dates', 'periodLabel', 'selectedLabel'
        ));
    }

    public function attendanceExport(Request $request)
    {
        $request->attributes->set('attendance_export', true);

        return $this->attendance($request);
    }

    public function attendance(Request $request)
    {
        $teacher = Auth::user();
        $today = Carbon::today()->toDateString();
        $myClasses = ClassSchedule::with(['subjectRecord', 'academicSection'])
            ->where('teacher_id', $teacher->id)
            ->orderBy('section')
            ->orderBy('start_time')
            ->get();
        $advisorySections = Schema::hasTable('academic_sections')
            ? DB::table('academic_sections')
                ->where('advisor_id', $teacher->id)
                ->orderBy('grade_level')
                ->orderBy('section_name')
                ->get(['id', 'section_name', 'grade_level'])
            : collect();

        $filters = $request->validate([
            'report_by' => ['nullable', 'in:advisory,subject'],
            'period' => ['nullable', 'in:daily,weekly,monthly'],
            'period_value' => ['nullable', 'string'],
            'section_id' => ['nullable', 'integer'],
            'schedule_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:ON-TIME,LATE,ABSENT'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50'],
        ]);
        $period = $filters['period'] ?? 'daily';
        $reportBy = $filters['report_by'] ?? ($advisorySections->isNotEmpty() ? 'advisory' : 'subject');
        $fallbackDate = Carbon::today();
        $periodValue = $filters['period_value'] ?? match ($period) {
            'weekly' => $fallbackDate->format('o-\WW'),
            'monthly' => $fallbackDate->format('Y-m'),
            default => $fallbackDate->toDateString(),
        };
        $periodIsValid = match ($period) {
            'weekly' => preg_match('/^(\d{4})-W(\d{2})$/D', $periodValue, $weekMatches)
                && (int) $weekMatches[1] > 0
                && Carbon::now()->setISODate((int) $weekMatches[1], (int) $weekMatches[2], Carbon::MONDAY)->format('o-W') === $weekMatches[1] . '-' . $weekMatches[2],
            'monthly' => (bool) preg_match('/^[1-9]\d{3}-(0[1-9]|1[0-2])$/D', $periodValue),
            default => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/D', $periodValue)
                && checkdate((int) substr($periodValue, 5, 2), (int) substr($periodValue, 8, 2), (int) substr($periodValue, 0, 4)),
        };
        if (!$periodIsValid) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'period_value' => 'Select a valid date, week, or month for the chosen period.',
            ]);
        }
        $baseQuery = clone $query;
        $presentCount = (clone $baseQuery)->whereIn('attendance_logs.status', ['ON-TIME', 'PRESENT', 'Present'])->count();
        $lateCount = (clone $baseQuery)->whereIn('attendance_logs.status', ['LATE', 'Late'])->count();
        $absentCount = (clone $baseQuery)->whereIn('attendance_logs.status', ['ABSENT', 'Absent'])->count();
        $totalScanned = (clone $baseQuery)->count();
        $recentLogs = $query->select('attendance_logs.*', 'users.first_name', 'users.last_name', 'users.id_number', 'users.strand', 'users.section')
            ->latest('attendance_logs.attendance_date')->latest('attendance_logs.id')
            ->paginate(15)->withQueryString();

        $periodDate = match ($period) {
            'weekly' => Carbon::now()->setISODate((int) $weekMatches[1], (int) $weekMatches[2], Carbon::MONDAY)->startOfDay(),
            'monthly' => Carbon::createFromFormat('!Y-m', $periodValue)->startOfDay(),
            default => Carbon::parse($periodValue)->startOfDay(),
        };
        $periodStart = match ($period) {
            'weekly' => $periodDate->copy()->startOfWeek(Carbon::MONDAY),
            'monthly' => $periodDate->copy()->startOfMonth(),
            default => $periodDate->copy(),
        };
        $periodEnd = match ($period) {
            'weekly' => $periodStart->copy()->endOfWeek(Carbon::SUNDAY),
            'monthly' => $periodStart->copy()->endOfMonth(),
            default => $periodDate->copy(),
        };
        $dates = collect();
        for ($date = $periodStart->copy(); $date->lte($periodEnd); $date->addDay()) {
            $dates->push($date->copy());
        }

        $availableSelections = $reportBy === 'advisory' ? $advisorySections : $myClasses;
        $selectionField = $reportBy === 'advisory' ? 'section_id' : 'schedule_id';
        $selectedId = $filters[$selectionField] ?? $availableSelections->first()?->id;
        $selectedItem = $availableSelections->firstWhere('id', (int) $selectedId);
        if ($selectedId && !$selectedItem) {
            abort(403, 'You may only view attendance for your assigned advisory sections or subject classes.');
        }

        $sectionId = $reportBy === 'advisory'
            ? ($selectedItem->id ?? null)
            : ($selectedItem->section_id ?? null);
        $sectionName = $reportBy === 'advisory'
            ? ($selectedItem->section_name ?? null)
            : ($selectedItem?->academicSection?->section_name ?: ($selectedItem->section ?? null));
        $scheduleIds = $reportBy === 'subject' && $selectedItem ? collect([$selectedItem->id]) : collect();

        $studentsQuery = User::query()
            ->where('role_id', 3)
            ->select(['id', 'id_number', 'first_name', 'last_name', 'section', 'strand']);
        if ($selectedItem && $sectionName) {
            $studentsQuery->where(function ($scope) use ($sectionId, $sectionName, $reportBy, $selectedItem) {
                $scope->where('section', $sectionName);
                if ($sectionId && Schema::hasTable('section_student')) {
                    $scope->orWhereIn('id', DB::table('section_student')->select('student_id')->where('section_id', $sectionId));
                }
                if (Schema::hasTable('attendance_logs')) {
                    $scope->orWhereIn('id', function ($logs) use ($sectionId, $sectionName, $reportBy, $selectedItem) {
                        $logs->select('user_id')->from('attendance_logs')
                            ->where('attendance_type', $reportBy);
                        if ($reportBy === 'advisory') {
                            $logs->where(function ($section) use ($sectionId, $sectionName) {
                                if ($sectionId) {
                                    $section->where('advisory_section_id', $sectionId);
                                }
                                $section->orWhere(function ($legacy) use ($sectionName) {
                                    $legacy->whereNull('advisory_section_id')
                                        ->whereIn('user_id', User::query()->select('id')->where('section', $sectionName));
                                });
                            });
                        } else {
                            $logs->where('class_schedule_id', $selectedItem->id);
                        }
                    });
                }
            });
        } elseif ($selectedItem && $sectionId && Schema::hasTable('section_student')) {
            $studentsQuery->whereIn('id', DB::table('section_student')->select('student_id')->where('section_id', $sectionId));
        } else {
            $studentsQuery->whereRaw('1 = 0');
        }
        $studentsQuery->when($filters['search'] ?? null, function ($query, $term) {
            $query->where(function ($search) use ($term) {
                $search->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('id_number', 'like', "%{$term}%");
            });
        });
        $students = $studentsQuery->orderBy('last_name')->orderBy('first_name')->get();

        $logs = collect();
        if ($selectedItem) {
            $logsQuery = DB::table('attendance_logs')
                ->join('users', 'attendance_logs.user_id', '=', 'users.id')
                ->where('users.role_id', 3)
                ->where('attendance_logs.attendance_type', $reportBy)
                ->whereBetween('attendance_logs.attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->whereIn('attendance_logs.user_id', $students->pluck('id')->isNotEmpty() ? $students->pluck('id') : [0]);
            if ($reportBy === 'advisory') {
                $logsQuery->where(function ($scope) use ($selectedItem, $students) {
                    $scope->where('attendance_logs.advisory_section_id', $selectedItem->id)
                        ->orWhere(function ($legacy) use ($students) {
                            $legacy->whereNull('attendance_logs.advisory_section_id')
                                ->whereIn('attendance_logs.user_id', $students->pluck('id'));
                        });
                });
            } else {
                $logsQuery->whereIn('attendance_logs.class_schedule_id', $scheduleIds);
            }
            if (!empty($filters['status'])) {
                $logsQuery->whereRaw('UPPER(attendance_logs.status) = ?', [$filters['status']]);
            }
            $logs = $logsQuery->select([
                'attendance_logs.user_id',
                'attendance_logs.attendance_date',
                'attendance_logs.time_in',
                'attendance_logs.time_out',
                'attendance_logs.status',
                'users.first_name',
                'users.last_name',
                'users.id_number',
                'users.section',
                'users.strand',
            ])->orderBy('attendance_logs.id')->get();
        }

        $logsByStudentDate = $logs->keyBy(
            fn ($log) => $log->user_id . '|' . Carbon::parse($log->attendance_date)->toDateString()
        );
        $reportRows = $students->map(function ($student) use ($dates, $logsByStudentDate) {
            $dailyLogs = [];
            foreach ($dates as $date) {
                $dailyLogs[$date->toDateString()] = $logsByStudentDate->get(
                    $student->id . '|' . $date->toDateString()
                );
            }
            return (object) ['student' => $student, 'logs' => $dailyLogs];
        });
        if (!empty($filters['status'])) {
            $reportRows = $reportRows->filter(
                fn ($row) => collect($row->logs)->contains(
                    fn ($log) => $log && strtoupper((string) $log->status) === $filters['status']
                )
            )->values();
        }
        $summary = [
            'students' => $students->count(),
            'total' => $logs->count(),
            'present' => $logs->filter(fn ($log) => in_array(strtoupper((string) $log->status), ['ON-TIME', 'PRESENT'], true))->count(),
            'late' => $logs->filter(fn ($log) => strtoupper((string) $log->status) === 'LATE')->count(),
            'clocked_out' => $logs->filter(fn ($log) => !empty($log->time_out))->count(),
        ];
        $perPage = (int) ($filters['per_page'] ?? 20);
        $page = max(1, (int) $request->input('page', 1));
        $recentLogs = new \Illuminate\Pagination\LengthAwarePaginator(
            $reportRows->forPage($page, $perPage)->values(),
            $reportRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        $periodLabel = match ($period) {
            'weekly' => $periodStart->format('M j') . ' – ' . $periodEnd->format('M j, Y'),
            'monthly' => $periodStart->format('F Y'),
            default => $periodDate->format('F j, Y'),
        };
        $selectedLabel = !$selectedItem ? 'No assigned class' : ($reportBy === 'advisory'
            ? "Grade {$selectedItem->grade_level} · {$selectedItem->section_name}"
            : ($selectedItem->subject_name ?: $selectedItem->subjectRecord?->name ?: $selectedItem->subject_code ?: 'Subject')
                . ' · ' . ($selectedItem->academicSection?->section_name ?: $selectedItem->section ?: 'Section'));

        if ($request->attributes->get('attendance_export')) {
            $filename = 'attendance-' . $periodStart->toDateString() . '-to-' . $periodEnd->toDateString() . '.csv';

            return response()->streamDownload(function () use ($logs) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Student', 'ID Number', 'Section', 'Strand', 'Date', 'Status', 'Time In', 'Time Out']);
                foreach ($logs as $log) {
                    fputcsv($file, [
                        trim($log->last_name . ', ' . $log->first_name),
                        $log->id_number,
                        $log->section,
                        $log->strand,
                        $log->attendance_date,
                        $log->status,
                        $log->time_in,
                        $log->time_out,
                    ]);
                }
                fclose($file);
            }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
        }

        return view('teacher.attendance', compact(
            'teacher', 'myClasses', 'advisorySections', 'reportBy', 'period', 'periodValue',
            'periodStart', 'periodEnd', 'periodLabel', 'dates', 'selectedId', 'selectedLabel',
            'recentLogs', 'summary', 'today'
        ));
    }

    public function classList($scheduleId)
    {
        $teacher = Auth::user();

        $schedule = ClassSchedule::where('id', $scheduleId)
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        $students = User::where('role_id', 3) 
            ->when(Schema::hasColumn('users', 'section'), function($q) use ($schedule) {
                $q->where('section', $schedule->section);
            })
            ->get();

        return view('teacher.class-list', compact('teacher', 'schedule', 'students'));
    }

    public function evaluationsIndex()
    {
        $teacher = Auth::user();
        $peers = User::where('role_id', 2)->where('id', '!=', $teacher->id)->get();

        $activeCycle = Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')->where('status', 'active')->where('is_active', 1)->orderByDesc('id')->first()
            : null;
        $evaluationActive = (bool) $activeCycle;
        $evaluationStatusCycle = $activeCycle ?: (Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')->orderByDesc('id')->first()
            : null);

        $peerForm = Schema::hasTable('evaluation_forms')
            ? DB::table('evaluation_forms')->where('form_type', 'peer')->where('is_active', 1)->orderByDesc('id')->first()
            : null;
        $selfForm = Schema::hasTable('evaluation_forms')
            ? DB::table('evaluation_forms')->where('form_type', 'self')->where('is_active', 1)->orderByDesc('id')->first()
            : null;

        $peerQuestionsQuery = Schema::hasTable('evaluation_questions')
            ? DB::table('evaluation_questions')->where('form_type', 'peer')->where('is_active', 1)
            : null;
        if ($peerQuestionsQuery && $peerForm && Schema::hasColumn('evaluation_questions', 'form_id')) {
            $peerQuestionsQuery->where('form_id', $peerForm->id);
        }
        $peerQuestions = $peerQuestionsQuery ? $peerQuestionsQuery->orderBy('order_num')->get() : collect([]);
            
        $selfQuestionsQuery = Schema::hasTable('evaluation_questions')
            ? DB::table('evaluation_questions')->where('form_type', 'self')->where('is_active', 1)
            : null;
        if ($selfQuestionsQuery && $selfForm && Schema::hasColumn('evaluation_questions', 'form_id')) {
            $selfQuestionsQuery->where('form_id', $selfForm->id);
        }
        $selfQuestions = $selfQuestionsQuery ? $selfQuestionsQuery->orderBy('order_num')->get() : collect([]);

        $peerRatingScales = ($peerForm && Schema::hasTable('evaluation_rating_scales'))
            ? DB::table('evaluation_rating_scales')->where('form_id', $peerForm->id)->orderBy('order_num')->get()
            : collect([]);
        $selfRatingScales = ($selfForm && Schema::hasTable('evaluation_rating_scales'))
            ? DB::table('evaluation_rating_scales')->where('form_id', $selfForm->id)->orderBy('order_num')->get()
            : collect([]);

        $loadEvaluationSections = function ($form, $questions) {
            if (!$form || !Schema::hasTable('evaluation_sections')) {
                return $questions->groupBy('category')->map(function ($items, $title) {
                    return (object) ['title' => $title, 'subheadings' => collect(), 'direct_questions' => $items->values()];
                })->values();
            }

            return DB::table('evaluation_sections')
                ->where('form_id', $form->id)
                ->orderBy('order_num')
                ->get()
                ->map(function ($section) use ($questions) {
                    $section->subheadings = Schema::hasTable('evaluation_subheadings')
                        ? DB::table('evaluation_subheadings')->where('section_id', $section->id)->orderBy('order_num')->get()->map(function ($subheading) use ($questions) {
                            $subheading->questions = $questions->where('subheading_id', $subheading->id)->values();
                            return $subheading;
                        })
                        : collect();
                    $section->direct_questions = $questions->where('section_id', $section->id)->whereNull('subheading_id')->values();
                    return $section;
                });
        };

        $peerSections = $loadEvaluationSections($peerForm, $peerQuestions);
        $selfSections = $loadEvaluationSections($selfForm, $selfQuestions);
        $hasPeerLikertQuestions = $peerQuestions->contains(fn ($question) => ($question->type ?? 'likert') === 'likert');

        $evaluatedPeerIds = collect();
        if ($activeCycle && Schema::hasTable('peer_evaluations')) {
            $evaluatedPeerQuery = DB::table('peer_evaluations')->where('evaluator_id', $teacher->id);
            if (Schema::hasColumn('peer_evaluations', 'evaluation_cycle_id')) {
                $evaluatedPeerQuery->where('evaluation_cycle_id', $activeCycle->id);
            }
            $evaluatedPeerIds = $evaluatedPeerQuery->pluck('evaluatee_id');
        }

        $selfEvaluationDone = false;
        if ($evaluationStatusCycle && Schema::hasTable('self_evaluations')) {
            $selfQuery = DB::table('self_evaluations')->where('teacher_id', $teacher->id);
            if (Schema::hasColumn('self_evaluations', 'evaluation_cycle_id')) {
                $selfQuery->where('evaluation_cycle_id', $evaluationStatusCycle->id);
            }
            $selfEvaluationDone = $selfQuery->exists();
        }

        $peerEvaluationDone = false;
        if ($evaluationStatusCycle && Schema::hasTable('peer_evaluations')) {
            $peerEvaluationDone = DB::table('peer_evaluations')
                ->where('evaluator_id', $teacher->id)
                ->where('evaluation_cycle_id', $evaluationStatusCycle->id)
                ->exists();
        }
        $evaluationCompleted = $peerEvaluationDone || $selfEvaluationDone;
        $allTeacherEvaluationsDone = $peerEvaluationDone && $selfEvaluationDone;

        return view('teacher.evaluations.adaptive', compact(
            'teacher', 'peers', 'peerQuestions', 'selfQuestions', 'activeCycle',
            'evaluationActive', 'evaluatedPeerIds', 'selfEvaluationDone', 'peerEvaluationDone', 'evaluationCompleted', 'allTeacherEvaluationsDone',
            'evaluationStatusCycle', 'peerForm', 'selfForm', 'peerRatingScales', 'selfRatingScales', 'peerSections',
            'selfSections', 'hasPeerLikertQuestions'
        ));
    }

    public function storePeerEvaluation(Request $request)
    {
        $activeCycle = Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')->where('status', 'active')->where('is_active', 1)->orderByDesc('id')->first()
            : null;
        if (!$activeCycle) {
            return back()->with('error', 'There is no active faculty evaluation at this time.');
        }

        $request->validate([
            'evaluatee_id' => 'required|exists:users,id',
            'ratings'      => 'required|array',
        ]);

        $likertQuestionIds = Schema::hasTable('evaluation_questions')
            ? DB::table('evaluation_questions')
                ->where('form_type', 'peer')
                ->when(Schema::hasColumn('evaluation_questions', 'type'), fn ($query) => $query->where('type', 'likert'))
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all()
            : [];
        $scores = collect((array) $request->input('ratings', []))
            ->filter(fn ($score, $questionId) => (empty($likertQuestionIds) || in_array((string) $questionId, $likertQuestionIds, true)) && is_numeric($score))
            ->values()
            ->all();
        $averageScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;

        $duplicateQuery = DB::table('peer_evaluations')
            ->where('evaluator_id', Auth::id())
            ->where('evaluatee_id', $request->evaluatee_id);
        if (Schema::hasColumn('peer_evaluations', 'evaluation_cycle_id')) {
            $duplicateQuery->where('evaluation_cycle_id', $activeCycle->id);
        }
        if ($duplicateQuery->exists()) {
            return back()->with('error', 'You have already evaluated this faculty member for the active cycle.');
        }

        $evaluationData = [
            'evaluator_id'  => Auth::id(),
            'evaluatee_id'  => $request->evaluatee_id,
            'average_score' => $averageScore,
            'comments'      => $request->input('comments'),
            'answers'       => json_encode($request->input('ratings', [])),
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
        if (Schema::hasColumn('peer_evaluations', 'evaluation_cycle_id')) {
            $evaluationData['evaluation_cycle_id'] = $activeCycle->id;
        }
        DB::table('peer_evaluations')->insert($evaluationData);

        return back()->with('success', 'Peer evaluation submitted successfully!');
    }

    public function storeSelfEvaluation(Request $request)
    {
        $activeCycle = Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')->where('status', 'active')->where('is_active', 1)->orderByDesc('id')->first()
            : null;
        if (!$activeCycle) {
            return back()->with('error', 'There is no active faculty evaluation at this time.');
        }

        $request->validate([
            'ratings' => 'required|array',
        ]);

        $likertQuestionIds = Schema::hasTable('evaluation_questions')
            ? DB::table('evaluation_questions')
                ->where('form_type', 'self')
                ->when(Schema::hasColumn('evaluation_questions', 'type'), fn ($query) => $query->where('type', 'likert'))
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all()
            : [];
        $scores = collect((array) $request->input('ratings', []))
            ->filter(fn ($score, $questionId) => (empty($likertQuestionIds) || in_array((string) $questionId, $likertQuestionIds, true)) && is_numeric($score))
            ->values()
            ->all();
        $averageScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;

        $evaluationData = [
            'teacher_id'    => Auth::id(),
            'average_score' => $averageScore,
            'comments'      => $request->input('comments'),
            'answers'       => json_encode($request->input('ratings', [])),
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
        if (Schema::hasColumn('self_evaluations', 'evaluation_cycle_id')) {
            $evaluationData['evaluation_cycle_id'] = $activeCycle->id;
        }
        DB::table('self_evaluations')->insert($evaluationData);

        return back()->with('success', 'Self-evaluation submitted successfully!');
    }

}