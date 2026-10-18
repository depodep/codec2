@extends('layouts.app')

@section('title', 'Management Dashboard | SIATRACK')

@section('content')
<div class="min-h-screen bg-slate-50/70">
    <header class="sticky top-0 z-20 flex flex-col justify-between gap-4 border-b-2 border-slate-200 bg-white px-6 py-5 shadow-xs lg:flex-row lg:items-center lg:px-10">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#590d0d] text-amber-300"><i class="fa-solid fa-chart-pie"></i></div>
            <div>
                <h1 class="text-xl font-black tracking-tight text-slate-900">Management Dashboard</h1>
                <p class="mt-0.5 text-xs font-bold text-slate-500">Read-only school overview and approved management tools</p>
            </div>
        </div>
        @if($canEvaluateFaculty)
            <a href="{{ route('management.evaluations.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#590d0d] px-4 py-2.5 text-xs font-black text-amber-300 transition hover:bg-[#43090c]">
                <i class="fa-solid fa-star-half-stroke"></i> Principal Faculty Evaluation
            </a>
        @endif
    </header>

    <main class="mx-auto max-w-[1700px] space-y-5 px-4 py-6 sm:px-6 lg:px-10">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        @if(!$canViewMasterData)
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
                <h2 class="text-base font-black text-amber-950">Dashboard and master-list access is not enabled</h2>
                <p class="mt-1 text-sm font-semibold text-amber-800">Ask an administrator to enable “View dashboard, attendance summaries, and master lists” for your account.</p>
            </section>
        @else
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach([
                    ['label' => 'Overall Attendance Rate', 'value' => number_format($metrics['overallAttendanceRate'], 1).'%', 'icon' => 'fa-chart-line', 'iconBg' => 'bg-emerald-50', 'iconColor' => 'text-emerald-700'],
                    ['label' => 'Total Students', 'value' => number_format($metrics['studentCount']), 'icon' => 'fa-user-graduate', 'iconBg' => 'bg-blue-50', 'iconColor' => 'text-blue-700'],
                    ['label' => 'Total Teachers', 'value' => number_format($metrics['teacherCount']), 'icon' => 'fa-chalkboard-user', 'iconBg' => 'bg-violet-50', 'iconColor' => 'text-violet-700'],
                ] as $card)
                    <article class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">{{ $card['label'] }}</p>
                            <p class="mt-1 text-3xl font-black tracking-tight text-slate-900">{{ $card['value'] }}</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $card['iconBg'] }} {{ $card['iconColor'] }}"><i class="fa-solid {{ $card['icon'] }}"></i></span>
                    </article>
                @endforeach
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="mb-4">
                    <h2 class="text-base font-black text-slate-900">Today’s Attendance by Section</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Distinct students with attendance records today; unrecorded is enrolled students without a present, late, or absent record.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px] text-left text-xs">
                        <thead class="border-y border-slate-200 bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                            <tr><th class="px-3 py-3">Section</th><th class="px-3 py-3">Enrolled</th><th class="px-3 py-3">Present</th><th class="px-3 py-3">Late</th><th class="px-3 py-3">Absent</th><th class="px-3 py-3">Unrecorded</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($metrics['todaySectionAttendance'] as $row)
                                <tr class="font-semibold text-slate-700">
                                    <td class="px-3 py-3 font-black text-slate-900">{{ $row->section }}</td>
                                    <td class="px-3 py-3">{{ number_format($row->enrolled) }}</td>
                                    <td class="px-3 py-3 text-emerald-700">{{ number_format($row->present) }}</td>
                                    <td class="px-3 py-3 text-amber-700">{{ number_format($row->late) }}</td>
                                    <td class="px-3 py-3 text-rose-700">{{ number_format($row->absent) }}</td>
                                    <td class="px-3 py-3">{{ number_format($row->unrecorded) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-8 text-center font-semibold text-slate-500">No student attendance records found for today.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($metrics['todaySectionAttendance']->isNotEmpty())
                    <div class="mt-5 space-y-3 border-t border-slate-100 pt-4">
                        <h3 class="text-[10px] font-black uppercase tracking-wide text-slate-500">Recorded attendance share by section</h3>
                        @foreach($metrics['todaySectionAttendance'] as $row)
                            @php
                                $total = max(1, $row->enrolled);
                                $presentWidth = round(($row->present / $total) * 100, 2);
                                $lateWidth = round(($row->late / $total) * 100, 2);
                                $absentWidth = round(($row->absent / $total) * 100, 2);
                                $unrecordedWidth = max(0, 100 - $presentWidth - $lateWidth - $absentWidth);
                            @endphp
                            <div class="grid grid-cols-[8rem_1fr_4rem] items-center gap-3 text-[10px]">
                                <span class="truncate font-bold text-slate-700">{{ $row->section }}</span>
                                <div class="flex h-3 overflow-hidden rounded-full bg-slate-100" role="img" aria-label="{{ $row->section }} attendance distribution">
                                    <span class="bg-emerald-500" style="width: {{ $presentWidth }}%"></span>
                                    <span class="bg-amber-400" style="width: {{ $lateWidth }}%"></span>
                                    <span class="bg-rose-500" style="width: {{ $absentWidth }}%"></span>
                                    <span class="bg-slate-300" style="width: {{ $unrecordedWidth }}%"></span>
                                </div>
                                <span class="text-right font-black text-slate-600">{{ $row->enrolled }}</span>
                            </div>
                        @endforeach
                        <div class="flex flex-wrap gap-4 pt-1 text-[10px] font-bold text-slate-600">
                            <span><i class="fa-solid fa-square mr-1 text-emerald-500"></i>Present</span>
                            <span><i class="fa-solid fa-square mr-1 text-amber-400"></i>Late</span>
                            <span><i class="fa-solid fa-square mr-1 text-rose-500"></i>Absent</span>
                            <span><i class="fa-solid fa-square mr-1 text-slate-300"></i>Unrecorded</span>
                        </div>
                    </div>
                @endif
            </section>

            <section x-data="{ tab: 'students' }" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4">
                    <div>
                        <h2 class="text-base font-black text-slate-900">School Master Lists</h2>
                        <p class="mt-1 text-xs font-semibold text-slate-500">View-only student, faculty, section, and schedule information.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['students' => 'Students', 'teachers' => 'Teachers', 'sections' => 'Sections', 'schedules' => 'Schedules'] as $key => $label)
                            <button type="button" @click="tab = '{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-[#590d0d] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="rounded-lg px-3 py-2 text-[11px] font-black">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                <div x-show="tab === 'students'" class="overflow-x-auto">
                    <table class="w-full min-w-[700px] text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Student Name</th><th class="px-4 py-3">Grade</th><th class="px-4 py-3">Section</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($students as $student)
                                <tr class="font-semibold text-slate-700"><td class="px-4 py-3 font-bold text-slate-900">{{ $student->last_name }}, {{ $student->first_name }}</td><td class="px-4 py-3">{{ $student->grade_level ?: '—' }}</td><td class="px-4 py-3">{{ $student->section ?: 'Unassigned' }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">No student records found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="border-t border-slate-100 p-3">{{ $students->links() }}</div>
                </div>

                <div x-show="tab === 'teachers'" x-cloak class="overflow-x-auto">
                    <table class="w-full min-w-[700px] text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Teacher Name</th><th class="px-4 py-3">Employee ID</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Advisory Section</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($teachers as $teacher)
                                <tr class="font-semibold text-slate-700"><td class="px-4 py-3 font-bold text-slate-900">{{ $teacher->last_name }}, {{ $teacher->first_name }}</td><td class="px-4 py-3 font-mono">{{ $teacher->id_number ?: '—' }}</td><td class="px-4 py-3">{{ $teacher->email ?: '—' }}</td><td class="px-4 py-3">{{ $teacher->section ?: '—' }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No teacher records found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="border-t border-slate-100 p-3">{{ $teachers->links() }}</div>
                </div>

                <div x-show="tab === 'sections'" x-cloak class="overflow-x-auto">
                    <table class="w-full min-w-[600px] text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Grade</th><th class="px-4 py-3">Section</th><th class="px-4 py-3">Strand</th><th class="px-4 py-3">Adviser</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($sections as $section)
                                <tr class="font-semibold text-slate-700"><td class="px-4 py-3">{{ $section->grade_level ?: '—' }}</td><td class="px-4 py-3 font-bold text-slate-900">{{ $section->section_name }}</td><td class="px-4 py-3">{{ $section->strand ?: '—' }}</td><td class="px-4 py-3">{{ trim(($section->adviser_first_name ?? '').' '.($section->adviser_last_name ?? '')) ?: 'Unassigned' }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No sections found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div x-show="tab === 'schedules'" x-cloak class="overflow-x-auto">
                    <table class="w-full min-w-[850px] text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Day / Time</th><th class="px-4 py-3">Subject</th><th class="px-4 py-3">Grade & Section</th><th class="px-4 py-3">Teacher</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($schedules as $schedule)
                                <tr class="font-semibold text-slate-700">
                                    <td class="px-4 py-3"><span class="font-bold text-slate-900">{{ $schedule->day ?: '—' }}</span><br>{{ $schedule->start_time }}–{{ $schedule->end_time }}</td>
                                    <td class="px-4 py-3">{{ $schedule->subject_name ?: 'Unassigned' }}<span class="block text-[10px] text-slate-500">{{ $schedule->subject_code ?: '' }}</span></td>
                                    <td class="px-4 py-3">{{ $schedule->academic_grade_level ?: $schedule->grade_level ?: '—' }} · {{ $schedule->academic_section_name ?: $schedule->section ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ trim(($schedule->teacher_first_name ?? '').' '.($schedule->teacher_last_name ?? '')) ?: 'Unassigned' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No class schedules found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="border-t border-slate-100 p-3">{{ $schedules->links() }}</div>
                </div>
            </section>
        @endif
    </main>
</div>
@endsection
