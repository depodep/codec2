@extends('layouts.app')

@section('title', 'Student Attendance | SIATRACK')

@section('content')
<div class="min-h-screen bg-slate-50/70">
    <header class="sticky top-0 z-20 flex flex-col justify-between gap-3 border-b-2 border-slate-200 bg-white px-4 py-3 shadow-xs sm:flex-row sm:items-center sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#590d0d] text-amber-300 shadow-xs">
                <i class="fa-solid fa-clipboard-user"></i>
            </div>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-lg font-black tracking-tight text-slate-900 sm:text-xl">Student Attendance</h1>
                    <span class="rounded-md border border-amber-300 bg-amber-100 px-2 py-0.5 text-[9px] font-black uppercase text-amber-900">Faculty Portal</span>
                </div>
                <p class="truncate text-[11px] font-semibold text-slate-500">{{ $teacher->first_name }} {{ $teacher->last_name }} <span class="px-1 text-slate-300">·</span> {{ $selectedLabel }} <span class="px-1 text-slate-300">·</span> {{ $periodLabel }}</p>
            </div>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <a href="{{ route('teacher.dashboard') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 transition hover:border-amber-400 hover:bg-amber-50">
                <i class="fa-solid fa-gauge text-[#8b1818]"></i> Dashboard
            </a>
            <a href="{{ route('attendance.start') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-[#590d0d] px-3 py-2 text-xs font-black text-amber-300 transition hover:bg-[#43090c]">
                <i class="fa-solid fa-play"></i> Start Attendance
            </a>
        </div>
    </header>

    <main class="mx-auto w-full max-w-[1700px] space-y-4 p-4 sm:p-5 lg:p-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
            <div class="flex items-center gap-2 bg-[#590d0d] px-4 py-2.5 text-xs font-black text-amber-300">
                <i class="fa-solid fa-filter"></i> Attendance Filters
            </div>
            <form id="teacher-attendance-filters" method="GET" action="{{ route('teacher.attendance') }}" class="grid grid-cols-1 gap-3 p-3 sm:grid-cols-2 xl:grid-cols-6 xl:items-end">
                <label class="text-[11px] font-black text-slate-700">
                    Attendance type
                    <select id="attendance-report-by" name="report_by" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 focus:border-amber-500 focus:outline-none">
                        @if($advisorySections->isNotEmpty())
                            <option value="advisory" @selected($reportBy === 'advisory')>Advisory</option>
                        @endif
                        @if($myClasses->isNotEmpty())
                            <option value="subject" @selected($reportBy === 'subject')>Subject class</option>
                        @endif
                        @if($advisorySections->isEmpty() && $myClasses->isEmpty())
                            <option value="advisory" selected>No assigned classes</option>
                        @endif
                    </select>
                </label>

                @if($advisorySections->isNotEmpty())
                    <label id="advisory-section-filter" class="text-[11px] font-black text-slate-700 {{ $reportBy === 'advisory' ? '' : 'hidden' }}">
                        Advisory section
                        <select name="section_id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 focus:border-amber-500 focus:outline-none">
                            @foreach($advisorySections as $section)
                                <option value="{{ $section->id }}" @selected((string) $selectedId === (string) $section->id)>Grade {{ $section->grade_level }} · {{ $section->section_name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif

                @if($myClasses->isNotEmpty())
                    <label id="subject-class-filter" class="text-[11px] font-black text-slate-700 {{ $reportBy === 'subject' ? '' : 'hidden' }}">
                        Subject class
                        <select name="schedule_id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 focus:border-amber-500 focus:outline-none">
                            @foreach($myClasses as $class)
                                <option value="{{ $class->id }}" @selected((string) $selectedId === (string) $class->id)>
                                    {{ $class->academicSection?->section_name ?: $class->section ?: 'Section' }} · {{ $class->subject_name ?: $class->subjectRecord?->name ?: $class->subject_code ?: 'Subject' }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                @endif

                <label class="text-[11px] font-black text-slate-700">
                    Report period
                    <select id="attendance-period" name="period" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 focus:border-amber-500 focus:outline-none">
                        <option value="daily" @selected($period === 'daily')>Today / Daily</option>
                        <option value="weekly" @selected($period === 'weekly')>Weekly</option>
                        <option value="monthly" @selected($period === 'monthly')>Monthly</option>
                    </select>
                </label>

                <label class="text-[11px] font-black text-slate-700">
                    <span id="period-value-label">{{ $period === 'weekly' ? 'Select week' : ($period === 'monthly' ? 'Select month' : 'Select date') }}</span>
                    <input id="attendance-period-value" name="period_value" type="{{ $period === 'weekly' ? 'week' : ($period === 'monthly' ? 'month' : 'date') }}" value="{{ $periodValue }}" data-today="{{ $today }}" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 focus:border-amber-500 focus:outline-none">
                </label>

                <label class="text-[11px] font-black text-slate-700">
                    Status
                    <select name="status" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 focus:border-amber-500 focus:outline-none">
                        <option value="">All statuses</option>
                        <option value="ON-TIME" @selected(request('status') === 'ON-TIME')>Present / on time</option>
                        <option value="LATE" @selected(request('status') === 'LATE')>Late</option>
                        <option value="ABSENT" @selected(request('status') === 'ABSENT')>Absent</option>
                    </select>
                </label>

                <div class="flex flex-wrap items-center gap-2 xl:col-span-6">
                    <label class="min-w-[180px] flex-1 text-[11px] font-black text-slate-700 sm:max-w-xs">
                        Find student
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Name or ID number" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 placeholder:text-slate-400 focus:border-amber-500 focus:outline-none">
                    </label>
                    <label class="w-28 text-[11px] font-black text-slate-700">
                        Rows
                        <select name="per_page" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 focus:border-amber-500 focus:outline-none">
                            @foreach([10, 20, 50] as $count)
                                <option value="{{ $count }}" @selected((int) request('per_page', 20) === $count)>{{ $count }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-[#590d0d] px-4 py-2.5 text-xs font-black text-white transition hover:bg-[#43090c]">
                        <i class="fa-solid fa-magnifying-glass"></i> Apply
                    </button>
                    <a href="{{ route('teacher.attendance') }}" class="rounded-lg border border-slate-300 px-3 py-2.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50">Reset</a>
                    @if($selectedId)
                        <a href="{{ route('teacher.attendance.export', request()->query()) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-3 py-2.5 text-xs font-black text-white transition hover:bg-emerald-800">
                            <i class="fa-solid fa-file-arrow-down"></i> Export CSV
                        </a>
                    @endif
                </div>
                @if($errors->any())
                    <p class="text-xs font-bold text-red-700 xl:col-span-6">{{ $errors->first() }}</p>
                @endif
            </form>
        </section>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach([
                ['label' => 'Students in class', 'value' => $summary['students'], 'icon' => 'fa-users', 'color' => 'blue'],
                ['label' => 'Attendance records', 'value' => $summary['total'], 'icon' => 'fa-clipboard-check', 'color' => 'slate'],
                ['label' => 'Present / on time', 'value' => $summary['present'], 'icon' => 'fa-user-check', 'color' => 'emerald'],
                ['label' => 'Late / clocked out', 'value' => $summary['late'] . ' / ' . $summary['clocked_out'], 'icon' => 'fa-clock', 'color' => 'amber'],
            ] as $card)
                <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 shadow-xs">
                    <div class="min-w-0">
                        <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-0.5 text-xl font-black leading-tight text-slate-900">{{ is_numeric($card['value']) ? number_format((int) $card['value']) : $card['value'] }}</p>
                    </div>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-{{ $card['color'] }}-50 text-{{ $card['color'] }}-700"><i class="fa-solid {{ $card['icon'] }}"></i></span>
                </div>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                <div>
                    <h2 class="text-sm font-black text-slate-900">{{ $selectedLabel }}</h2>
                    <p class="mt-0.5 text-[10px] font-semibold text-slate-500">
                        {{ $period === 'daily' ? 'Daily log' : ($period === 'weekly' ? 'Weekly logs' : 'Monthly logs') }} · {{ $periodLabel }}
                    </p>
                </div>
                <p class="text-[10px] font-semibold text-slate-500">P = present · L = late · — = no log, not marked absent · times are in / out</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-[11px]">
                    <thead class="bg-[#590d0d] text-[9px] font-black uppercase tracking-wide text-amber-200">
                        <tr>
                            <th class="sticky left-0 z-10 min-w-52 bg-[#590d0d] px-3 py-2.5">Student</th>
                            @if($period === 'daily')
                                <th class="min-w-20 px-2 py-2.5">Status</th>
                                <th class="min-w-20 px-2 py-2.5">Time in</th>
                                <th class="min-w-20 px-2 py-2.5">Time out</th>
                            @else
                                @foreach($dates as $date)
                                    <th class="min-w-[76px] px-1.5 py-2 text-center">
                                        {{ $date->format('d') }}
                                        <span class="block text-[8px] font-semibold text-amber-100/70">{{ $date->format('D') }}</span>
                                    </th>
                                @endforeach
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentLogs as $row)
                            <tr class="hover:bg-amber-50/40">
                                <td class="sticky left-0 bg-white px-3 py-2 align-top">
                                    <p class="font-black text-slate-800">{{ $row->student->last_name }}, {{ $row->student->first_name }}</p>
                                    <p class="text-[9px] text-slate-500">{{ $row->student->id_number ?: 'No ID' }} · {{ $row->student->section ?: '—' }}</p>
                                </td>
                                @if($period === 'daily')
                                    @php($log = $row->logs[$dates->first()->toDateString()] ?? null)
                                    @php($status = strtoupper((string) ($log->status ?? '')))
                                    <td class="px-2 py-2">
                                        @if($log)
                                            <span class="rounded-md px-2 py-1 text-[9px] font-black {{ in_array($status, ['ON-TIME', 'PRESENT']) ? 'bg-emerald-50 text-emerald-700' : ($status === 'LATE' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') }}">{{ $log->status }}</span>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 font-bold text-slate-700">{{ $log?->time_in ? \Carbon\Carbon::parse($log->time_in)->format('g:i A') : '—' }}</td>
                                    <td class="px-2 py-2 font-bold text-slate-700">{{ $log?->time_out ? \Carbon\Carbon::parse($log->time_out)->format('g:i A') : '—' }}</td>
                                @else
                                    @foreach($dates as $date)
                                        @php($log = $row->logs[$date->toDateString()] ?? null)
                                        @php($status = strtoupper((string) ($log->status ?? '')))
                                        <td class="px-1 py-1.5 text-center align-top">
                                            @if($log)
                                                <span title="{{ $log->status }}" class="mx-auto inline-flex h-5 w-5 items-center justify-center rounded text-[9px] font-black {{ in_array($status, ['ON-TIME', 'PRESENT']) ? 'bg-emerald-100 text-emerald-700' : ($status === 'LATE' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">{{ in_array($status, ['ON-TIME', 'PRESENT']) ? 'P' : ($status === 'LATE' ? 'L' : 'A') }}</span>
                                                <span class="mt-1 block whitespace-nowrap text-[8px] font-semibold leading-tight text-slate-600">{{ $log->time_in ? \Carbon\Carbon::parse($log->time_in)->format('g:i') : '—' }} / {{ $log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('g:i') : '—' }}</span>
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $period === 'daily' ? 4 : $dates->count() + 1 }}" class="px-4 py-12 text-center text-slate-400">
                                    <i class="fa-regular fa-folder-open mb-2 text-xl text-slate-300"></i>
                                    <p class="font-bold">No students or attendance logs match these filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <footer class="flex flex-col justify-between gap-2 border-t border-slate-100 px-4 py-2.5 text-[10px] text-slate-500 sm:flex-row sm:items-center">
                <span>Showing {{ $recentLogs->firstItem() ?? 0 }}–{{ $recentLogs->lastItem() ?? 0 }} of {{ $recentLogs->total() }} students</span>
                @if($recentLogs->hasPages())
                    <div class="[&_*]:!mb-0">{{ $recentLogs->links() }}</div>
                @endif
            </footer>
        </section>
    </main>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const form = document.getElementById('teacher-attendance-filters');
        const reportBy = document.getElementById('attendance-report-by');
        const period = document.getElementById('attendance-period');
        const periodValue = document.getElementById('attendance-period-value');
        const periodLabel = document.getElementById('period-value-label');
        const advisoryFilter = document.getElementById('advisory-section-filter');
        const subjectFilter = document.getElementById('subject-class-filter');

        const isoWeekValue = (date) => {
            const day = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
            day.setUTCDate(day.getUTCDate() + 4 - (day.getUTCDay() || 7));
            const yearStart = new Date(Date.UTC(day.getUTCFullYear(), 0, 1));
            const week = Math.ceil((((day - yearStart) / 86400000) + 1) / 7);
            return `${day.getUTCFullYear()}-W${String(week).padStart(2, '0')}`;
        };

        const updatePeriodPicker = (resetValue = false) => {
            const type = period.value === 'weekly' ? 'week' : (period.value === 'monthly' ? 'month' : 'date');
            periodValue.type = type;
            periodLabel.textContent = period.value === 'weekly' ? 'Select week' : (period.value === 'monthly' ? 'Select month' : 'Select date');
            if (resetValue || !periodValue.value) {
                const today = new Date(`${periodValue.dataset.today}T12:00:00`);
                periodValue.value = period.value === 'weekly'
                    ? isoWeekValue(today)
                    : (period.value === 'monthly' ? periodValue.dataset.today.slice(0, 7) : periodValue.dataset.today);
            }
        };

        updatePeriodPicker();
        period.addEventListener('change', () => updatePeriodPicker(true));
        reportBy?.addEventListener('change', () => {
            if (advisoryFilter) advisoryFilter.classList.toggle('hidden', reportBy.value !== 'advisory');
            if (subjectFilter) subjectFilter.classList.toggle('hidden', reportBy.value !== 'subject');
            form.requestSubmit();
        });
    })();
</script>
@endpush
