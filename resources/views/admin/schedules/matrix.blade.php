@extends('layouts.app')

@section('title', 'Master Weekly Timetable - SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-100/60">

    <!-- Top Header Bar -->
    <header class="bg-white border-b-2 border-slate-200 px-6 sm:px-10 lg:px-12 py-6 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs w-full">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#8b1818] text-white flex items-center justify-center text-lg shadow-sm shrink-0">
                <i class="fa-solid fa-calendar-days text-amber-300"></i>
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Master Weekly Timetable Matrix</h1>
                </div>
                <p class="text-xs text-slate-500 font-bold mt-1">Faculty Load Allocation Grid (Click any subject card to view its student class list)</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span class="px-4 py-2 rounded-xl text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-300 uppercase tracking-wide shadow-2xs inline-flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Department: General Faculty
            </span>
            <a href="{{ route('admin.schedules.index') }}" class="px-5 py-2.5 rounded-2xl bg-white hover:bg-slate-50 text-slate-700 font-black text-xs uppercase tracking-wider border-2 border-slate-200 shadow-2xs transition">
                <i class="fa-solid fa-table-list mr-1.5 text-[#8b1818]"></i> List View
            </a>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="py-8 px-6 sm:px-10 lg:px-12 w-full space-y-8 flex-1 max-w-[1700px] mx-auto">

        @php
            $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        @endphp

        <!-- TIMETABLE MATRIX GRID -->
        <div class="bg-white rounded-3xl border-2 border-slate-200 shadow-sm overflow-hidden w-full">
            
            <!-- Days Header Bar -->
            <div class="grid grid-cols-1 md:grid-cols-5 bg-[#8b1818] text-white font-black text-xs uppercase tracking-wider text-center divide-y md:divide-y-0 md:divide-x divide-red-950/40">
                @foreach($weekdays as $day)
                    <div class="py-4 px-3">{{ $day }}</div>
                @endforeach
            </div>

            <!-- Timetable Body Columns -->
            <div class="grid grid-cols-1 md:grid-cols-5 divide-y md:divide-y-0 md:divide-x divide-slate-200 bg-slate-50/30 min-h-[450px]">
                @foreach($weekdays as $day)
                    @php
                        // Salain ang mga klase base sa araw
                        $daySchedules = $schedules->filter(function($sched) use ($day) {
                            $schedDay = $sched->day ?? $sched->day_of_week ?? '';
                            return strcasecmp(trim($schedDay), trim($day)) === 0;
                        });
                    @endphp

                    <div class="p-4 flex flex-col gap-4 bg-white/50">
                        @forelse($daySchedules as $schedule)
                            <!-- Subject Card (Katulad sa picture) -->
                            <div onclick="window.location.href='#'" class="relative bg-white p-4 rounded-2xl border-2 border-slate-200 shadow-xs hover:shadow-md hover:border-[#8b1818] transition-all cursor-pointer group overflow-hidden">
                                <!-- Red accent line on the left -->
                                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-[#8b1818]"></div>

                                <div class="flex items-center justify-between gap-2 mb-2 pl-2">
                                    <span class="px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[10px] font-black uppercase border border-slate-200">
                                        {{ $schedule->subject_code ?? 'SUBJ' }}
                                    </span>
                                    <span class="px-2.5 py-0.5 rounded-md bg-red-50 text-[#8b1818] text-[10px] font-black uppercase border border-red-200">
                                        Sec: {{ $schedule->section ?? optional($schedule->academicSection)->section_name ?? 'N/A' }}
                                    </span>
                                </div>

                                <div class="pl-2 mb-4">
                                    <h4 class="text-sm font-black text-slate-900 group-hover:text-[#8b1818] transition leading-tight">
                                        {{ $schedule->subject_name ?? $schedule->subject ?? optional($schedule->subjectRecord)->name ?? 'Unnamed Subject' }}
                                    </h4>
                                </div>

                                <div class="pt-3 border-t border-slate-100 pl-2 flex items-center justify-between text-xs text-slate-600 font-bold">
                                    <span class="inline-flex items-center gap-1.5 font-mono text-[11px]">
                                        <i class="fa-regular fa-clock text-amber-600"></i>
                                        {{ $schedule->start_time }} - {{ $schedule->end_time }}
                                    </span>
                                    <i class="fa-solid fa-arrow-right text-[#8b1818] group-hover:translate-x-1 transition"></i>
                                </div>
                            </div>
                        @empty
                            <!-- Empty State per Day -->
                            <div class="h-full flex flex-col items-center justify-center py-16 text-slate-400 italic text-xs font-semibold select-none">
                                No classes
                            </div>
                        @endforelse
                    </div>
                @endforeach
            </div>

        </div>

    </main>
</div>
@endsection