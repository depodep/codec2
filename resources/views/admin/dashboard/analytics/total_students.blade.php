@extends('layouts.app')

@section('title', 'Student Management & Records - SIATRACK')

@section('content')
<div class="w-full min-h-screen flex flex-col bg-slate-50/70 p-6 lg:p-10 space-y-8">
    
    <!-- Top Header Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border-2 border-slate-200 shadow-xs">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('admin.dashboard') }}" class="w-10 h-10 rounded-xl bg-red-50 hover:bg-red-100 text-[#8b1818] border border-red-200 flex items-center justify-center transition cursor-pointer shrink-0 shadow-2xs">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">Student Management & Records</h1>
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 uppercase">Records Portal</span>
                </div>
                <p class="text-xs text-slate-500 font-bold mt-0.5">Comprehensive tracking of student demographics, section population, and dynamic filtering</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3.5 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-extrabold text-slate-700">
                <i class="fa-solid fa-users text-[#8b1818] mr-1.5"></i> Filtered Total: <span id="filteredCountBadge">{{ method_exists($students ?? null, 'total') ? $students->total() : count($students ?? []) }}</span>
            </span>
        </div>
    </div>

    <!-- Filter Panel Card (Placed at the Top) -->
    <div class="bg-white p-6 lg:p-8 rounded-3xl border-2 border-slate-200 shadow-xs w-full">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Directory Filtering & Controls</h3>
                <p class="text-xs text-slate-400 font-bold mt-0.5">Filter student logs by Strand or Class Section to update analytics dynamically</p>
            </div>
            <div class="w-9 h-9 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center text-xs shadow-2xs">
                <i class="fa-solid fa-filter"></i>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.students.analytics') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Strand Filter -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Strand / Track</label>
                    <select name="strand" class="w-full py-2.5 px-3 text-xs font-bold text-slate-800 border-2 border-slate-200 rounded-xl bg-white focus:border-[#8b1818] outline-none cursor-pointer">
                        <option value="">All Strands</option>
                        <option value="STEM" {{ request('strand') == 'STEM' ? 'selected' : '' }}>STEM</option>
                        <option value="ABM" {{ request('strand') == 'ABM' ? 'selected' : '' }}>ABM</option>
                        <option value="HUMSS" {{ request('strand') == 'HUMSS' ? 'selected' : '' }}>HUMSS</option>
                        <option value="GAS" {{ request('strand') == 'GAS' ? 'selected' : '' }}>GAS</option>
                    </select>
                </div>

                <!-- Complete Section Filter Dropdown -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Section</label>
                    <select name="section" class="w-full py-2.5 px-3 text-xs font-bold text-slate-800 border-2 border-slate-200 rounded-xl bg-white focus:border-[#8b1818] outline-none cursor-pointer">
                        <option value="">All Sections</option>
                        @foreach($sections ?? [] as $sec)
                            <option value="{{ $sec }}" {{ request('section') == $sec ? 'selected' : '' }}>Section {{ $sec }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3">
                <a href="{{ route('admin.students.analytics') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-extrabold rounded-xl transition text-center">
                    Reset Filters
                </a>
                <button type="submit" class="px-6 py-2.5 bg-[#8b1818] hover:bg-[#721313] text-white text-xs font-black rounded-xl shadow-md shadow-red-950/20 transition cursor-pointer flex items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                    <span>Apply Filters</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Side-by-Side Analytics Graphs Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
        
        <!-- Both Genders Bar Graph Card -->
        <div class="bg-white p-6 lg:p-8 rounded-3xl border-2 border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Gender Demographic Overview</h3>
                    <p class="text-xs text-slate-400 font-bold mt-0.5">Male vs Female student population distribution</p>
                </div>
                <div class="w-9 h-9 rounded-xl bg-red-50 border border-red-200 text-[#8b1818] flex items-center justify-center text-xs shadow-2xs">
                    <i class="fa-solid fa-chart-column"></i>
                </div>
            </div>
            <!-- FIXED WRAPPER HEIGHT (h-64) -->
            <div class="relative h-64 w-full">
                <canvas id="genderComparisonBarGraph"></canvas>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-around text-center text-xs font-extrabold">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span>
                    <span class="text-slate-600">Male: <span class="text-slate-900 font-black">{{ $maleCount ?? 0 }}</span></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-pink-500 inline-block"></span>
                    <span class="text-slate-600">Female: <span class="text-slate-900 font-black">{{ $femaleCount ?? 0 }}</span></span>
                </div>
            </div>
        </div>

        <!-- Section Population Pie Graph Card -->
        <div class="bg-white p-6 lg:p-8 rounded-3xl border-2 border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Section Population Ratio</h3>
                    <p class="text-xs text-slate-400 font-bold mt-0.5">Overall distribution of students across sections</p>
                </div>
                <div class="w-9 h-9 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center text-xs shadow-2xs">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
            </div>
            <!-- FIXED WRAPPER HEIGHT (h-64) -->
            <div class="relative h-64 w-full">
                <canvas id="sectionPopulationPieChart"></canvas>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 text-center text-xs font-extrabold text-slate-600">
                Total Enrolled Sections: <span class="text-[#8b1818] font-black">{{ count($sectionPopulations ?? []) }} Active Sections</span>
            </div>
        </div>

    </div>

    <!-- Live Database Student Directory Logs Table -->
    <div class="bg-white p-6 lg:p-8 rounded-3xl border-2 border-slate-200 shadow-xs space-y-6 w-full">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-900 tracking-tight">Student Directory Logs</h3>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Showing live database records matching the selected parameters</p>
            </div>
            <span class="text-[11px] font-extrabold px-3 py-1 bg-red-50 text-[#8b1818] border border-red-200 rounded-lg">
                SIATRACK Database Registry
            </span>
        </div>
        
        <div class="overflow-x-auto rounded-2xl border-2 border-slate-200 shadow-2xs">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50/90 text-slate-700 uppercase font-black tracking-wider border-b-2 border-slate-200">
                    <tr>
                        <th class="py-4 px-5">Student Name</th>
                        <th class="py-4 px-5 text-center">LRN / School ID</th>
                        <th class="py-4 px-5 text-center">Strand</th>
                        <th class="py-4 px-5 text-center">Gender</th>
                        <th class="py-4 px-5 text-center">Section</th>
                        <th class="py-4 px-5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                    @forelse($students ?? [] as $st)
                    <tr class="hover:bg-red-50/40 transition">
                        <td class="py-4 px-5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-red-100 border border-red-200 text-[#8b1818] text-xs font-black flex items-center justify-center shrink-0 shadow-2xs">
                                    {{ strtoupper(substr($st->first_name ?? 'S', 0, 1)) }}{{ strtoupper(substr($st->last_name ?? 'T', 0, 1)) }}
                                </div>
                                <span class="text-slate-900 font-extrabold">{{ $st->first_name }} {{ $st->last_name }}</span>
                            </div>
                        </td>
                        <td class="py-4 px-5 text-center font-mono font-bold text-slate-700">{{ $st->id_number ?? 'N/A' }}</td>
                        <td class="py-4 px-5 text-center">
                            <span class="px-2.5 py-1 rounded-md bg-slate-100 border border-slate-200 text-slate-700 text-[10px] font-black">
                                {{ $st->strand ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="py-4 px-5 text-center text-slate-600 font-bold">{{ $st->gender ?? 'N/A' }}</td>
                        <td class="py-4 px-5 text-center font-bold text-slate-700">Section {{ $st->section ?? 'N/A' }}</td>
                        <td class="py-4 px-5 text-center">
                            @if($st->is_active ?? true)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> ACTIVE
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-300 shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span> INACTIVE
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400 font-bold">
                            <div class="flex flex-col items-center justify-center space-y-2">
                                <i class="fa-solid fa-folder-open text-3xl text-slate-300"></i>
                                <p>No student records found matching the database query parameters.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($students ?? null, 'links'))
        <div class="pt-2">
            {{ $students->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const maleTotal = Number("{{ $maleCount ?? 0 }}");
    const femaleTotal = Number("{{ $femaleCount ?? 0 }}");

    // 1. Gender Comparison Bar Graph
    const barEl = document.getElementById('genderComparisonBarGraph');
    if (barEl) {
        new Chart(barEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Male', 'Female'],
                datasets: [{
                    data: [maleTotal, femaleTotal],
                    backgroundColor: ['rgba(59, 130, 246, 0.85)', 'rgba(236, 72, 153, 0.85)'],
                    borderColor: ['#2563eb', '#db2777'],
                    borderWidth: 2,
                    borderRadius: 8,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0, font: { weight: 'bold' } }, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false }, ticks: { font: { weight: 'bold' } } }
                }
            }
        });
    }

    // 2. Section Population Pie Chart
    const sectionLabels = {!! json_encode(array_keys($sectionPopulations ?? [])) !!};
    const sectionData = {!! json_encode(array_values($sectionPopulations ?? [])) !!};

    const pieEl = document.getElementById('sectionPopulationPieChart');
    if (pieEl) {
        new Chart(pieEl.getContext('2d'), {
            type: 'pie',
            data: {
                labels: sectionLabels.map(label => 'Section ' + label),
                datasets: [{
                    data: sectionData,
                    backgroundColor: [
                        'rgba(245, 158, 11, 0.85)',  // Amber
                        'rgba(59, 130, 246, 0.85)',  // Blue
                        'rgba(16, 185, 129, 0.85)',  // Emerald
                        'rgba(236, 72, 153, 0.85)',  // Pink
                        'rgba(139, 24, 24, 0.85)',   // Maroon (#8b1818)
                        'rgba(99, 102, 241, 0.85)'   // Indigo
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { weight: 'bold', size: 11 }, boxWidth: 12 }
                    }
                }
            }
        });
    }
});
</script>
@endsection