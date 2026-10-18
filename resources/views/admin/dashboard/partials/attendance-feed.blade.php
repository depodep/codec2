<!-- Section: Real-Time Attendance Stream -->
<div class="p-6 lg:p-8 bg-white rounded-3xl border-2 border-slate-200 shadow-xs space-y-6 w-full">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b-2 border-slate-100 w-full">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 flex items-center justify-center text-sm font-black shadow-2xs">
                <i class="fa-solid fa-tower-broadcast text-[#590d0d]"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-900 tracking-tight">Live Gate Attendance Feed</h2>
                <p class="text-xs text-slate-500 font-bold mt-0.5">Real-time NFC card taps recorded today</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="relative w-full md:w-80">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="feedSearch" onkeyup="filterFeedTable()" placeholder="Filter live scans..."
                       class="w-full pl-9 pr-4 py-2.5 text-xs font-bold border-2 border-slate-200 rounded-2xl focus:border-[#590d0d] outline-none bg-white transition shadow-2xs">
            </div>
        </div>
    </div>

    <!-- Feed Table -->
    <div class="overflow-x-auto rounded-2xl border-2 border-slate-200 shadow-2xs w-full">
        <table class="w-full text-left border-collapse" id="feedTable">
            <thead>
                <tr class="bg-slate-50/90 text-slate-700 uppercase font-black tracking-wider text-xs border-b-2 border-slate-200">
                    <th class="py-4 px-5">Student Name</th>
                    <th class="py-4 px-5 text-center">LRN / School ID</th>
                    <th class="py-4 px-5 text-center">Academic Placement</th>
                    <th class="py-4 px-5 text-center">Time In</th>
                    <th class="py-4 px-5 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm font-semibold text-slate-800">
                @forelse($recentTaps as $tap)
                    @php
                        $trackLabel = match((int)($tap->track ?? $tap->strand ?? 0)) {
                            1 => 'Academic Track',
                            2 => 'Technical-Professional',
                            default => 'General Track'
                        };
                        $sectionLabel = match((int)($tap->section ?? 0)) {
                            1 => 'Amber',
                            2 => 'Crystal',
                            3 => 'Pearl',
                            4 => 'Turquoise',
                            default => !empty($tap->section) ? 'Sec. ' . $tap->section : null
                        };
                        $gradeLabel = !empty($tap->grade_level) ? 'Grade ' . $tap->grade_level : 'Grade 11';
                    @endphp
                    <tr class="hover:bg-red-50/40 transition tap-row">
                        <td class="py-4 px-5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-red-100 border border-red-200 text-[#590d0d] text-xs font-black flex items-center justify-center shrink-0 shadow-2xs">
                                    {{ strtoupper(substr($tap->first_name ?? 'S', 0, 1)) }}{{ strtoupper(substr($tap->last_name ?? 'T', 0, 1)) }}
                                </div>
                                <div>
                                    <span class="text-slate-900 font-extrabold block leading-tight student-name">{{ $tap->first_name }} {{ $tap->last_name }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-5 text-center text-slate-900 font-mono font-bold text-sm student-id">
                            {{ $tap->id_number ?? 'N/A' }}
                        </td>
                        <td class="py-4 px-5 text-center student-placement">
                            <div class="space-y-1">
                                <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-800 text-xs font-black border border-slate-300 shadow-2xs inline-block">
                                    {{ $gradeLabel }}
                                </span>
                                <div>
                                    <span class="px-2 py-0.5 rounded-md bg-red-50 text-[#590d0d] text-[10px] font-black border border-red-200">
                                        {{ $trackLabel }} @if($sectionLabel) • {{ $sectionLabel }} @endif
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-5 text-center text-slate-900 font-mono font-bold text-xs">
                            @if(!empty($tap->time_in))
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-50 border border-slate-200">
                                    <i class="fa-regular fa-clock text-amber-600 text-xs"></i>
                                    <span>{{ \Carbon\Carbon::parse($tap->time_in)->format('h:i:s A') }}</span>
                                </span>
                            @else
                                <span class="text-slate-400 font-sans font-semibold text-xs">--:--:--</span>
                            @endif
                        </td>
                        <td class="py-4 px-5 text-center">
                            @if(strtoupper($tap->status ?? '') === 'ON-TIME')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> ON-TIME
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-900 border border-amber-300 shadow-2xs">
                                    LATE
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-16 text-center text-slate-400 font-medium">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 border-2 border-slate-200 text-slate-400 flex items-center justify-center mx-auto mb-3.5 text-2xl shadow-2xs">
                                <i class="fa-solid fa-clipboard-list"></i>
                            </div>
                            <p class="text-base font-extrabold text-slate-800">No Attendance Taps Logged Today</p>
                            <p class="text-xs text-slate-500 font-semibold mt-1">Scans will appear here in real-time as students tap their NFC cards.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>