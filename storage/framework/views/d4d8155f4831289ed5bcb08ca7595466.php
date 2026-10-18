

<?php $__env->startSection('title', 'Teacher Dashboard | SIATRACK'); ?>

<?php $__env->startSection('content'); ?>
<div class="min-h-screen bg-slate-50/70">
    <header class="bg-white border-b-2 border-slate-200 px-6 lg:px-10 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-base shadow-xs"><i class="fa-solid fa-chart-line"></i></div>
            <div>
                <div class="flex items-center gap-2"><h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">Teacher Dashboard</h1><span class="px-2.5 py-0.5 rounded-md text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 uppercase">Faculty Portal</span></div>
                <p class="text-xs text-slate-500 font-bold mt-0.5">Attendance overview for <?php echo e($teacher->first_name); ?> <?php echo e($teacher->last_name); ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="px-3 py-2 rounded-xl bg-slate-100 border border-slate-200 text-xs font-black text-slate-700"><i class="fa-solid fa-calendar-days text-[#8b1818] mr-1"></i><?php echo e($activePeriod?->school_year ?? 'No active school year'); ?></span>
            <a href="<?php echo e(route('teacher.reports')); ?>" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#590d0d] text-amber-300 text-xs font-black hover:bg-[#43090c]"><i class="fa-solid fa-clipboard-check"></i> Attendance Report</a>
            <a href="<?php echo e(route('attendance.start')); ?>" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-amber-400 text-amber-950 text-xs font-black hover:bg-amber-300"><i class="fa-solid fa-play"></i> Start Attendance</a>
        </div>
    </header>

    <main class="px-6 lg:px-10 py-6 space-y-5 max-w-[1700px] mx-auto">
        <?php if(!empty($isEvaluationActive) && empty($allTeacherEvaluationsDone)): ?>
            <div class="rounded-2xl border-2 border-amber-300 bg-amber-50 p-4 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-start sm:items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 border border-amber-300 flex items-center justify-center shrink-0 text-base">
                        <i class="fa-solid fa-triangle-exclamation animate-bounce"></i>
                    </span>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm font-black text-amber-950">Faculty Evaluation is Now Active</h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-200 text-amber-900 border border-amber-300 uppercase">Action Required</span>
                        </div>
                        <p class="text-xs font-bold text-amber-800 mt-0.5">
                            <?php echo e($activeEvaluationCycle->name ?? 'Active Evaluation Cycle'); ?> is ongoing.
                            <?php if(empty($selfEvaluationDone) && empty($peerEvaluationDone)): ?>
                                Please complete your <span class="underline">Self-Evaluation</span> and <span class="underline">Peer Evaluation</span>.
                            <?php elseif(empty($selfEvaluationDone)): ?>
                                Your <span class="underline">Self-Evaluation</span> is still pending.
                            <?php elseif(empty($peerEvaluationDone)): ?>
                                Your <span class="underline">Peer Evaluation</span> is still pending.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <a href="<?php echo e(route('teacher.evaluations.index')); ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#590d0d] text-amber-300 text-xs font-black hover:bg-[#43090c] transition shrink-0 shadow-xs">
                    <i class="fa-solid fa-clipboard-check"></i> Evaluate Now
                </a>
            </div>
        <?php endif; ?>

        <?php if(!$activePeriod): ?><div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-bold text-amber-900"><i class="fa-solid fa-calendar-xmark mr-2"></i>No active school year is configured. Dashboard data is limited to the fallback date range.</div><?php endif; ?>

        <section class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <?php $__currentLoopData = [
                ['label' => 'Students', 'value' => $totalStudents, 'color' => 'blue', 'icon' => 'fa-users', 'href' => route('teacher.students')],
                ['label' => 'Advisory Class', 'value' => $advisoryCount, 'color' => 'amber', 'icon' => 'fa-users-viewfinder', 'href' => route('teacher.students')],
                ['label' => 'Subjects', 'value' => $subjectCount, 'color' => 'rose', 'icon' => 'fa-book-open', 'href' => route('teacher.schedules')],
                ['label' => 'Classes Attendance Rate', 'value' => $classAttendanceRate . '%', 'color' => 'emerald', 'icon' => 'fa-chart-line', 'href' => route('teacher.reports')],
                ['label' => 'Advisory Attendance Rate', 'value' => $advisoryAttendanceRate . '%', 'color' => 'slate', 'icon' => 'fa-users-viewfinder', 'href' => route('teacher.reports')],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e($card['href']); ?>" class="bg-white rounded-2xl border-2 border-slate-200 p-4 shadow-xs flex items-center justify-between gap-3 hover:border-[#8b1818] transition"><div><p class="text-[10px] font-black uppercase tracking-wider text-slate-500"><?php echo e($card['label']); ?></p><p class="text-2xl font-black text-slate-900 mt-1"><?php echo e(is_numeric($card['value']) ? number_format((float) $card['value']) : $card['value']); ?></p></div><span class="w-10 h-10 rounded-xl bg-<?php echo e($card['color']); ?>-50 border border-<?php echo e($card['color']); ?>-200 text-<?php echo e($card['color']); ?>-700 flex items-center justify-center shrink-0"><i class="fa-solid <?php echo e($card['icon']); ?>"></i></span></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </section>

        <form id="dashboardFilterForm" method="GET" action="<?php echo e(route('teacher.dashboard')); ?>" class="bg-white rounded-2xl border-2 border-slate-200 p-4 shadow-xs flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-[10px] font-black uppercase text-slate-500 mb-1">View</label>
                <select id="filterRange" name="range" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold focus:border-[#8b1818] outline-none">
                    <option value="daily" <?php if($range === 'daily'): echo 'selected'; endif; ?>>Today</option>
                    <option value="weekly" <?php if($range === 'weekly'): echo 'selected'; endif; ?>>Weekly</option>
                    <option value="monthly" <?php if($range === 'monthly'): echo 'selected'; endif; ?>>Monthly</option>
                </select>
            </div>
            <div>
                <label id="filterDateLabel" class="block text-[10px] font-black uppercase text-slate-500 mb-1"><?php echo e($range === 'weekly' ? 'Week date' : ($range === 'monthly' ? 'Month' : 'Date')); ?></label>
                <input id="filterDate" type="date" name="date" value="<?php echo e($selectedDate->toDateString()); ?>" min="<?php echo e($schoolYearStart->toDateString()); ?>" max="<?php echo e($schoolYearEnd->toDateString()); ?>" class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold focus:border-[#8b1818] outline-none">
            </div>
            <div class="flex items-center gap-2 pb-2">
                <span id="dateRangeText" class="text-xs font-bold text-slate-500"><?php echo e($rangeStart->format('M d, Y')); ?> - <?php echo e($rangeEnd->format('M d, Y')); ?></span>
                <span id="filterLoadingIndicator" class="hidden text-xs font-bold text-[#8b1818] items-center gap-1">
                    <i class="fa-solid fa-circle-notch fa-spin"></i> Updating...
                </span>
            </div>
        </form>

        
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            
            <article class="bg-white rounded-2xl border-2 border-slate-200 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-black text-slate-900">Attendance Trend</h2>
                        <p id="attendanceTrendSubtext" class="text-[11px] font-bold text-slate-500"><?php echo e($range === 'daily' ? 'Today' : ucfirst($range)); ?> attendance volume within the active school year</p>
                    </div>
                    <i class="fa-solid fa-chart-area text-[#8b1818]"></i>
                </div>
                <div class="relative h-64">
                    <canvas id="attendanceTrendChart"></canvas>
                </div>
            </article>

            
            <article class="bg-white rounded-2xl border-2 border-slate-200 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-black text-slate-900">Attendance Rate Trend (%)</h2>
                        <p id="attendanceRateTrendSubtext" class="text-[11px] font-bold text-slate-500">Advisory Class vs. Subject Classes rate over time</p>
                    </div>
                    <i class="fa-solid fa-chart-line text-emerald-600"></i>
                </div>
                <div class="relative h-64">
                    <canvas id="attendanceRateTrendChart"></canvas>
                </div>
            </article>
        </section>

        
        <section class="grid grid-cols-1 gap-5">
            <article class="bg-white rounded-2xl border-2 border-slate-200 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-base font-black text-slate-900">Student Distribution by Section</h2>
                        <p class="text-[11px] font-bold text-slate-500">Unique enrolled students per section; subjects taught are listed under each section</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-3 text-[10px] font-black text-slate-700 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-xl">
                            <span class="inline-flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full inline-block bg-blue-600"></span>
                                <span>Male</span>
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full inline-block bg-rose-500"></span>
                                <span>Female</span>
                            </span>
                        </div>
                        <i class="fa-solid fa-book-open text-[#8b1818]"></i>
                    </div>
                </div>

                <div class="relative h-80">
                    <canvas id="subjectChart"></canvas>
                </div>
            </article>
        </section>

    </main>
</div>
<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    let attendanceTrendChart = null;
    let subjectChart = null;

    // Stacked Bar Labels Plugin: draws counts inside each segment (Female & Male) and total on top
    const stackedBarLabelsPlugin = {
        id: 'stackedBarLabels',
        afterDatasetsDraw(chart) {
            const { ctx } = chart;
            const femaleMeta = chart.getDatasetMeta(0);
            const maleMeta = chart.getDatasetMeta(1);
            if (!femaleMeta || !maleMeta) return;

            const length = Math.max(femaleMeta.data.length, maleMeta.data.length);
            for (let i = 0; i < length; i++) {
                const femaleBar = femaleMeta.data[i];
                const maleBar = maleMeta.data[i];
                const femaleVal = Number(chart.data.datasets[0]?.data[i]) || 0;
                const maleVal = Number(chart.data.datasets[1]?.data[i]) || 0;
                const total = femaleVal + maleVal;

                ctx.save();
                ctx.textAlign = 'center';

                // Female count inside bottom segment (Pink)
                if (femaleVal > 0 && femaleBar) {
                    const h0 = Math.abs(femaleBar.base - femaleBar.y);
                    if (h0 >= 14) {
                        ctx.font = 'bold 11px sans-serif';
                        ctx.fillStyle = '#ffffff';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(femaleVal, femaleBar.x, (femaleBar.y + femaleBar.base) / 2);
                    }
                }

                // Male count inside top segment (Blue)
                if (maleVal > 0 && maleBar) {
                    const h1 = Math.abs(maleBar.base - maleBar.y);
                    if (h1 >= 14) {
                        ctx.font = 'bold 11px sans-serif';
                        ctx.fillStyle = '#ffffff';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(maleVal, maleBar.x, (maleBar.y + maleBar.base) / 2);
                    }
                }

                // Total count on top of the stacked bar
                if (total > 0) {
                    const topY = Math.min(
                        femaleBar ? femaleBar.y : Infinity,
                        maleBar && maleVal > 0 ? maleBar.y : (femaleBar ? femaleBar.y : Infinity)
                    );
                    const posX = (maleBar || femaleBar).x;
                    if (isFinite(topY)) {
                        ctx.font = 'bold 12px sans-serif';
                        ctx.fillStyle = '#0f172a';
                        ctx.textBaseline = 'bottom';
                        ctx.fillText(total, posX, topY - 4);
                    }
                }

                ctx.restore();
            }
        }
    };

    let attendanceRateTrendChart = null;

    // 1. Initialize Attendance Volume/Status Line Chart
    const initTrendChart = (labels, presents, lates, absents) => {
        const ctx = document.getElementById('attendanceTrendChart');
        if (!ctx) return;
        if (attendanceTrendChart) attendanceTrendChart.destroy();
        attendanceTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'Present', data: presents, borderColor: '#059669', backgroundColor: 'rgba(5,150,105,.12)', fill: true, tension: 0.35 },
                    { label: 'Late', data: lates, borderColor: '#d97706', backgroundColor: 'transparent', tension: 0.35 },
                    { label: 'Absent', data: absents, borderColor: '#e11d48', backgroundColor: 'transparent', tension: 0.35 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { usePointStyle: true, boxWidth: 8 } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    };

    // 1b. Initialize Attendance Rate (%) Trends Line Chart (Advisory vs Subject)
    const initRateTrendChart = (labels, advisoryRates, subjectRates) => {
        const ctx = document.getElementById('attendanceRateTrendChart');
        if (!ctx) return;
        if (attendanceRateTrendChart) attendanceRateTrendChart.destroy();
        attendanceRateTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'Advisory Class (%)', data: advisoryRates, borderColor: '#d97706', backgroundColor: 'rgba(217,119,6,.08)', fill: true, tension: 0.35 },
                    { label: 'Subject Classes (%)', data: subjectRates, borderColor: '#0284c7', backgroundColor: 'rgba(2,132,199,.08)', fill: true, tension: 0.35 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { usePointStyle: true, boxWidth: 8 } },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ' ' + ctx.dataset.label + ': ' + (ctx.parsed.y || 0) + '%'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: (val) => val + '%'
                        }
                    }
                }
            }
        });
    };

    // 2. Initialize Stacked Bar Chart with Female (bottom) & Male (top)
    const initStackedBarChart = (canvasId, labels, femaleData, maleData) => {
        const ctx = document.getElementById(canvasId);
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Female',
                        data: femaleData,
                        backgroundColor: '#ec4899',
                        borderRadius: { topLeft: 0, topRight: 0, bottomLeft: 4, bottomRight: 4 },
                        barThickness: 34,
                        stack: 'genderStack'
                    },
                    {
                        label: 'Male',
                        data: maleData,
                        backgroundColor: '#3b82f6',
                        borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                        barThickness: 34,
                        stack: 'genderStack'
                    }
                ]
            },
            plugins: [stackedBarLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: { top: 22, bottom: 5 }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#0f172a',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 11, weight: '600' },
                        padding: 10,
                        cornerRadius: 10,
                        callbacks: {
                            title: (tooltipItems) => {
                                const item = tooltipItems[0];
                                const label = item?.chart.data.labels[item.dataIndex];
                                const section = Array.isArray(label) ? label[0] : label;
                                return section ? 'Section: ' + section : '';
                            },
                            beforeBody: (tooltipItems) => {
                                const item = tooltipItems[0];
                                const label = item?.chart.data.labels[item.dataIndex];
                                const subjects = Array.isArray(label) ? label[1] : '';
                                return 'Subjects: ' + (subjects || 'None assigned');
                            },
                            label: (context) => {
                                const val = context.parsed.y || 0;
                                return ' ' + context.dataset.label + ': ' + val + ' students';
                            },
                            footer: (tooltipItems) => {
                                const total = tooltipItems.reduce((acc, item) => acc + (item.parsed.y || 0), 0);
                                return 'Total Enrolled: ' + total + ' students';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grace: '15%'
                    },
                    x: {
                        stacked: true,
                        ticks: {
                            autoSkip: false,
                            maxRotation: 0,
                            minRotation: 0,
                            font: { size: 12, weight: 'bold' },
                            color: '#334155'
                        },
                        grid: { display: false }
                    }
                }
            }
        });
    };

    // Initial render
    initTrendChart(<?php echo json_encode($chartDates, 15, 512) ?>, <?php echo json_encode($chartPresents, 15, 512) ?>, <?php echo json_encode($chartLates, 15, 512) ?>, <?php echo json_encode($chartAbsents, 15, 512) ?>);
    initRateTrendChart(<?php echo json_encode($chartDates, 15, 512) ?>, <?php echo json_encode($chartAdvisoryRates, 15, 512) ?>, <?php echo json_encode($chartSubjectRates, 15, 512) ?>);
    subjectChart = initStackedBarChart('subjectChart', <?php echo json_encode($subjectSummary->pluck('label')->values(), 15, 512) ?>, <?php echo json_encode($subjectFemaleCounts, 15, 512) ?>, <?php echo json_encode($subjectMaleCounts, 15, 512) ?>);

    // Dynamic Filter Trigger (JavaScript process on change, remove apply button)
    const rangeSelect = document.getElementById('filterRange');
    const dateInput = document.getElementById('filterDate');
    const dateRangeText = document.getElementById('dateRangeText');
    const loadingIndicator = document.getElementById('filterLoadingIndicator');
    const dateLabel = document.getElementById('filterDateLabel');
    const trendSubtext = document.getElementById('attendanceTrendSubtext');
    const rateTrendSubtext = document.getElementById('attendanceRateTrendSubtext');

    const updateDashboardData = async () => {
        const range = rangeSelect.value;
        const date = dateInput.value;

        if (dateLabel) {
            dateLabel.textContent = range === 'weekly' ? 'Week date' : (range === 'monthly' ? 'Month' : 'Date');
        }

        loadingIndicator.classList.remove('hidden');
        loadingIndicator.classList.add('inline-flex');

        try {
            const url = new URL('<?php echo e(route('teacher.dashboard')); ?>', window.location.origin);
            url.searchParams.set('range', range);
            url.searchParams.set('date', date);

            const res = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) throw new Error('Failed to load dashboard data');
            const data = await res.json();

            // Update URL without page reload
            window.history.replaceState({}, '', url.toString());

            // 1. Update text indicators
            if (dateRangeText && data.rangeStartFormatted && data.rangeEndFormatted) {
                dateRangeText.textContent = `${data.rangeStartFormatted} - ${data.rangeEndFormatted}`;
            }
            if (trendSubtext) {
                const rangeLabel = range === 'daily' ? 'Today' : range.charAt(0).toUpperCase() + range.slice(1);
                trendSubtext.textContent = `${rangeLabel} attendance volume within the active school year`;
            }
            if (rateTrendSubtext) {
                const rangeLabel = range === 'daily' ? 'Today' : range.charAt(0).toUpperCase() + range.slice(1);
                rateTrendSubtext.textContent = `${rangeLabel} Advisory vs. Subject attendance rate (%)`;
            }

            // 2. Update Attendance Trend Charts
            initTrendChart(data.chartDates, data.chartPresents, data.chartLates, data.chartAbsents);
            initRateTrendChart(data.chartDates, data.chartAdvisoryRates, data.chartSubjectRates);

            // 3. Update Subject Stacked Bar Chart
            if (subjectChart) subjectChart.destroy();
            subjectChart = initStackedBarChart('subjectChart', data.subjectLabels, data.subjectFemaleCounts, data.subjectMaleCounts);

        } catch (err) {
            console.error('Error fetching dashboard data:', err);
        } finally {
            loadingIndicator.classList.add('hidden');
            loadingIndicator.classList.remove('inline-flex');
        }
    };

    // Attach change event listeners (pure javascript process on change)
    if (rangeSelect) rangeSelect.addEventListener('change', updateDashboardData);
    if (dateInput) dateInput.addEventListener('change', updateDashboardData);
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views/teacher/dashboard.blade.php ENDPATH**/ ?>