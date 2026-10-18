<?php $__env->startSection('title', 'Attendance Rate - SIATRACK'); ?>

<?php $__env->startSection('content'); ?>
<div class="w-full min-h-screen flex flex-col bg-slate-50/70 p-6 lg:p-10 space-y-8">
    
    <!-- Top Header Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border-2 border-slate-200 shadow-xs">
        <div class="flex items-center gap-3.5">
            <a href="<?php echo e(route('admin.reports')); ?>" class="w-10 h-10 rounded-xl bg-red-50 hover:bg-red-100 text-[#8b1818] border border-red-200 flex items-center justify-center transition cursor-pointer shrink-0 shadow-2xs">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">Attendance Rate</h1>
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black bg-red-100 text-[#8b1818] border border-red-200 uppercase">Analytics & Monitoring</span>
                </div>
                <p class="text-xs text-slate-500 font-bold mt-0.5">Monitor student attendance, trends, and attendance concerns.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-200 text-xs font-extrabold text-slate-700 flex items-center gap-2 shadow-2xs">
                <i class="fa-solid fa-calendar-days text-[#8b1818]"></i>
                <span>School Year: <strong class="text-[#8b1818]"><?php echo e(request('school_year', $activeSchoolYear)); ?></strong></span>
            </span>
        </div>
    </div>

    <!-- Filter Panel Card -->
    <div class="bg-white p-6 lg:p-8 rounded-3xl border-2 border-slate-200 shadow-xs w-full">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Attendance Filtering & Parameters</h3>
                <p class="text-xs text-slate-400 font-bold mt-0.5">Filter records by academic placement, date range, or student demographic</p>
            </div>
            <div class="w-9 h-9 rounded-xl bg-red-50 border border-red-200 text-[#8b1818] flex items-center justify-center text-xs shadow-2xs">
                <i class="fa-solid fa-filter"></i>
            </div>
        </div>

        <form method="GET" action="<?php echo e(route('admin.reports.attendance-rate')); ?>" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- School Year -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">School Year</label>
                    <select name="school_year" class="w-full py-2.5 px-3 text-xs font-bold text-slate-800 border-2 border-slate-200 rounded-xl bg-white focus:border-[#8b1818] outline-none cursor-pointer">
                        <?php $__currentLoopData = $schoolYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($sy); ?>" <?php echo e(request('school_year', $activeSchoolYear) == $sy ? 'selected' : ''); ?>>S.Y. <?php echo e($sy); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <!-- Year Level -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Year Level</label>
                    <select name="grade_level" class="w-full py-2.5 px-3 text-xs font-bold text-slate-800 border-2 border-slate-200 rounded-xl bg-white focus:border-[#8b1818] outline-none cursor-pointer">
                        <option value="">All Year Levels</option>
                        <?php $__currentLoopData = $gradeLevels ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lvl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($lvl); ?>" <?php echo e(request('grade_level') == $lvl ? 'selected' : ''); ?>>Grade <?php echo e($lvl); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <!-- Section -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Section</label>
                    <select name="section" class="w-full py-2.5 px-3 text-xs font-bold text-slate-800 border-2 border-slate-200 rounded-xl bg-white focus:border-[#8b1818] outline-none cursor-pointer">
                        <option value="">All Sections</option>
                        <?php $__currentLoopData = $sections ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sec): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($sec); ?>" <?php echo e(request('section') == $sec ? 'selected' : ''); ?>>Section <?php echo e($sec); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <!-- Gender -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Gender</label>
                    <select name="gender" class="w-full py-2.5 px-3 text-xs font-bold text-slate-800 border-2 border-slate-200 rounded-xl bg-white focus:border-[#8b1818] outline-none cursor-pointer">
                        <option value="">All Genders</option>
                        <option value="Male" <?php echo e(request('gender') == 'Male' ? 'selected' : ''); ?>>Male</option>
                        <option value="Female" <?php echo e(request('gender') == 'Female' ? 'selected' : ''); ?>>Female</option>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Date From</label>
                    <input type="date" name="date_from" value="<?php echo e(request('date_from')); ?>" class="w-full py-2.5 px-3 text-xs font-bold text-slate-800 border-2 border-slate-200 rounded-xl bg-white focus:border-[#8b1818] outline-none">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Date To</label>
                    <input type="date" name="date_to" value="<?php echo e(request('date_to')); ?>" class="w-full py-2.5 px-3 text-xs font-bold text-slate-800 border-2 border-slate-200 rounded-xl bg-white focus:border-[#8b1818] outline-none">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3">
                <a href="<?php echo e(route('admin.reports.attendance-rate')); ?>" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-extrabold rounded-xl transition text-center">
                    Reset Filters
                </a>
                <button type="submit" class="px-6 py-2.5 bg-[#8b1818] hover:bg-[#721313] text-white text-xs font-black rounded-xl shadow-md shadow-red-950/20 transition cursor-pointer flex items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                    <span>Apply Filters</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Statistics Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 w-full">
        <div class="bg-white p-5 rounded-3xl border-2 border-slate-200 shadow-xs flex flex-col justify-between">
            <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Overall Rate</span>
            <h3 class="text-2xl lg:text-3xl font-black text-[#8b1818] tracking-tight mt-2"><?php echo e($overallRate); ?>%</h3>
        </div>
        <div class="bg-white p-5 rounded-3xl border-2 border-slate-200 shadow-xs flex flex-col justify-between">
            <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Present</span>
            <h3 class="text-2xl lg:text-3xl font-black text-emerald-600 tracking-tight mt-2"><?php echo e(number_format($presentCount)); ?></h3>
        </div>
        <div class="bg-white p-5 rounded-3xl border-2 border-slate-200 shadow-xs flex flex-col justify-between">
            <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Late</span>
            <h3 class="text-2xl lg:text-3xl font-black text-amber-600 tracking-tight mt-2"><?php echo e(number_format($lateCount)); ?></h3>
        </div>
        <div class="bg-white p-5 rounded-3xl border-2 border-slate-200 shadow-xs flex flex-col justify-between">
            <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Absent</span>
            <h3 class="text-2xl lg:text-3xl font-black text-rose-600 tracking-tight mt-2"><?php echo e(number_format($absentCount)); ?></h3>
        </div>
        <div class="bg-white p-5 rounded-3xl border-2 border-slate-200 shadow-xs flex flex-col justify-between">
            <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Excused</span>
            <h3 class="text-2xl lg:text-3xl font-black text-blue-600 tracking-tight mt-2"><?php echo e(number_format($excusedCount)); ?></h3>
        </div>
        <div class="bg-white p-5 rounded-3xl border-2 border-slate-200 shadow-xs flex flex-col justify-between">
            <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Total Logs</span>
            <h3 class="text-2xl lg:text-3xl font-black text-slate-800 tracking-tight mt-2"><?php echo e(number_format($totalRecords)); ?></h3>
        </div>
    </div>

    <!-- Charts Grid (Trend & Status Breakdown) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 w-full">
        <!-- Attendance Trend Line Graph -->
        <div class="bg-white p-6 lg:p-8 rounded-3xl border-2 border-slate-200 shadow-xs flex flex-col justify-between lg:col-span-2">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Attendance Trend Over Time</h3>
                    <p class="text-xs text-slate-400 font-bold mt-0.5">Daily attendance volume and percentage flow</p>
                </div>
                <div class="w-9 h-9 rounded-xl bg-red-50 border border-red-200 text-[#8b1818] flex items-center justify-center text-xs shadow-2xs">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
            </div>
            <div class="relative h-72 w-full flex items-center justify-center">
                <canvas id="attendanceTrendChart"></canvas>
            </div>
        </div>

        <!-- Status Breakdown Doughnut Chart -->
        <div class="bg-white p-6 lg:p-8 rounded-3xl border-2 border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Status Breakdown</h3>
                    <p class="text-xs text-slate-400 font-bold mt-0.5">Proportion of scan statuses</p>
                </div>
                <div class="w-9 h-9 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center text-xs shadow-2xs">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
            </div>
            <div class="relative h-64 w-full flex items-center justify-center">
                <canvas id="statusBreakdownChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Attendance by Section Bar Chart -->
    <div class="bg-white p-6 lg:p-8 rounded-3xl border-2 border-slate-200 shadow-xs w-full space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Attendance by Section</h3>
                <p class="text-xs text-slate-400 font-bold mt-0.5">Comparative attendance rates across class sections</p>
            </div>
            <div class="w-9 h-9 rounded-xl bg-red-50 border border-red-200 text-[#8b1818] flex items-center justify-center text-xs shadow-2xs">
                <i class="fa-solid fa-chart-column"></i>
            </div>
        </div>
        <div class="relative h-64 w-full">
            <canvas id="sectionAttendanceChart"></canvas>
        </div>
    </div>

    <!-- Students with Attendance Concerns Table -->
    <div class="bg-white p-6 lg:p-8 rounded-3xl border-2 border-slate-200 shadow-xs space-y-6 w-full">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-900 tracking-tight">Students with Attendance Concerns</h3>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Identified students requiring monitoring or intervention due to poor attendance</p>
            </div>
            <span class="text-[11px] font-extrabold px-3 py-1 bg-amber-100 text-amber-900 border border-amber-300 rounded-lg">
                Intervention Registry
            </span>
        </div>

        <div class="overflow-x-auto rounded-2xl border-2 border-slate-200 shadow-2xs">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50/90 text-slate-700 uppercase font-black tracking-wider border-b-2 border-slate-200">
                    <tr>
                        <th class="py-4 px-5">Student Name</th>
                        <th class="py-4 px-5 text-center">Student ID</th>
                        <th class="py-4 px-5 text-center">Section</th>
                        <th class="py-4 px-5 text-center">Present</th>
                        <th class="py-4 px-5 text-center">Late</th>
                        <th class="py-4 px-5 text-center">Absent</th>
                        <th class="py-4 px-5 text-center">Attendance Rate</th>
                        <th class="py-4 px-5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                    <?php $__empty_1 = true; $__currentLoopData = $concernsStudents ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cs): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-red-50/40 transition">
                        <td class="py-4 px-5">
                            <span class="text-slate-900 font-extrabold"><?php echo e($cs->first_name); ?> <?php echo e($cs->last_name); ?></span>
                        </td>
                        <td class="py-4 px-5 text-center font-mono font-bold text-slate-700"><?php echo e($cs->id_number ?? 'N/A'); ?></td>
                        <td class="py-4 px-5 text-center font-bold text-slate-700">Section <?php echo e($cs->section ?? 'N/A'); ?></td>
                        <td class="py-4 px-5 text-center text-emerald-600 font-black"><?php echo e($cs->present_count); ?></td>
                        <td class="py-4 px-5 text-center text-amber-600 font-black"><?php echo e($cs->late_count); ?></td>
                        <td class="py-4 px-5 text-center text-rose-600 font-black"><?php echo e($cs->absent_count); ?></td>
                        <td class="py-4 px-5 text-center font-black text-slate-900"><?php echo e($cs->attendance_rate); ?>%</td>
                        <td class="py-4 px-5 text-center">
                            <?php if($cs->status_badge === 'At Risk'): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-300">At Risk</span>
                            <?php else: ?>
                                <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-300">Monitor</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400 font-bold">
                            <i class="fa-solid fa-circle-check text-3xl text-emerald-500 mb-2"></i>
                            <p>No students currently flagged with attendance concerns.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Detailed Attendance Records Table -->
    <div class="bg-white p-6 lg:p-8 rounded-3xl border-2 border-slate-200 shadow-xs space-y-6 w-full">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-900 tracking-tight">Detailed Attendance Records</h3>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Complete database logs of gate scans and status entries</p>
            </div>
            <span class="text-[11px] font-extrabold px-3 py-1 bg-red-50 text-[#8b1818] border border-red-200 rounded-lg">
                SIATRACK Gate Log Registry
            </span>
        </div>

        <div class="overflow-x-auto rounded-2xl border-2 border-slate-200 shadow-2xs">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50/90 text-slate-700 uppercase font-black tracking-wider border-b-2 border-slate-200">
                    <tr>
                        <th class="py-4 px-5">Student ID</th>
                        <th class="py-4 px-5">Student Name</th>
                        <th class="py-4 px-5 text-center">Section</th>
                        <th class="py-4 px-5 text-center">Date</th>
                        <th class="py-4 px-5 text-center">Time</th>
                        <th class="py-4 px-5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                    <?php $__empty_1 = true; $__currentLoopData = $detailedRecords ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-red-50/40 transition">
                        <td class="py-4 px-5 font-mono font-bold text-slate-700"><?php echo e($dr->id_number ?? 'N/A'); ?></td>
                        <td class="py-4 px-5 font-extrabold text-slate-900"><?php echo e($dr->first_name); ?> <?php echo e($dr->last_name); ?></td>
                        <td class="py-4 px-5 text-center font-bold text-slate-700">Section <?php echo e($dr->section ?? 'N/A'); ?></td>
                        <td class="py-4 px-5 text-center font-bold text-slate-600"><?php echo e(\Carbon\Carbon::parse($dr->created_at)->format('M d, Y')); ?></td>
                        <td class="py-4 px-5 text-center font-mono text-slate-700"><?php echo e(\Carbon\Carbon::parse($dr->created_at)->format('h:i:s A')); ?></td>
                        <td class="py-4 px-5 text-center">
                            <?php
                                $status = strtoupper($dr->status ?? 'PRESENT');
                            ?>
                            <?php if($status === 'ON-TIME' || $status === 'PRESENT'): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300">PRESENT</span>
                            <?php elseif($status === 'LATE'): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-900 border border-amber-300">LATE</span>
                            <?php elseif($status === 'ABSENT'): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-300">ABSENT</span>
                            <?php else: ?>
                                <span class="px-3 py-1 rounded-full text-xs font-black bg-blue-100 text-blue-800 border border-blue-300">EXCUSED</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400 font-bold">
                            <i class="fa-solid fa-folder-open text-3xl text-slate-300 mb-2"></i>
                            <p>No detailed attendance logs found matching the selected filters.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if(method_exists($detailedRecords ?? null, 'links')): ?>
        <div class="pt-2">
            <?php echo e($detailedRecords->withQueryString()->links()); ?>

        </div>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Attendance Trend Line Chart
    const trendDates = <?php echo json_encode($trendData->pluck('log_date')); ?>;
    const trendTotals = <?php echo json_encode($trendData->pluck('total')); ?>;

    const ctxTrend = document.getElementById('attendanceTrendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: trendDates,
            datasets: [{
                label: 'Attendance Taps',
                data: trendTotals,
                borderColor: '#8b1818',
                backgroundColor: 'rgba(139, 24, 24, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#8b1818',
                pointRadius: 4
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

    // 2. Status Breakdown Doughnut Chart
    const presentCount = <?php echo e($presentCount); ?>;
    const lateCount = <?php echo e($lateCount); ?>;
    const absentCount = <?php echo e($absentCount); ?>;
    const excusedCount = <?php echo e($excusedCount); ?>;

    const ctxStatus = document.getElementById('statusBreakdownChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Late', 'Absent', 'Excused'],
            datasets: [{
                data: [presentCount, lateCount, absentCount, excusedCount],
                backgroundColor: ['#10b981', '#f59e0b', '#f43f5e', '#3b82f6'],
                borderWidth: 2,
                borderColor: '#ffffff'
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

    // 3. Attendance by Section Bar Chart
    const sectionLabels = <?php echo json_encode(array_keys($sectionAttendance)); ?>;
    const sectionValues = <?php echo json_encode(array_values($sectionAttendance)); ?>;

    const ctxSection = document.getElementById('sectionAttendanceChart').getContext('2d');
    new Chart(ctxSection, {
        type: 'bar',
        data: {
            labels: sectionLabels.map(s => 'Section ' + s),
            datasets: [{
                label: 'Attendance Rate (%)',
                data: sectionValues,
                backgroundColor: 'rgba(139, 24, 24, 0.85)',
                borderColor: '#721313',
                borderWidth: 2,
                borderRadius: 8,
                barThickness: 35
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%', font: { weight: 'bold' } }, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false }, ticks: { font: { weight: 'bold' } } }
            }
        }
    });
});
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views\admin\dashboard\analytics\attendance-rate.blade.php ENDPATH**/ ?>