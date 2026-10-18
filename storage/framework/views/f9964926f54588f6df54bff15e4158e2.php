

<?php $__env->startSection('title', 'Student Attendance | SIATRACK'); ?>

<?php $__env->startSection('content'); ?>
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
                <p class="truncate text-[11px] font-semibold text-slate-500"><?php echo e($teacher->first_name); ?> <?php echo e($teacher->last_name); ?> <span class="px-1 text-slate-300">·</span> <?php echo e($selectedLabel); ?> <span class="px-1 text-slate-300">·</span> <?php echo e($periodLabel); ?></p>
            </div>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <a href="<?php echo e(route('teacher.dashboard')); ?>" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 transition hover:border-amber-400 hover:bg-amber-50">
                <i class="fa-solid fa-gauge text-[#8b1818]"></i> Dashboard
            </a>
            <a href="<?php echo e(route('attendance.start')); ?>" class="inline-flex items-center gap-1.5 rounded-xl bg-[#590d0d] px-3 py-2 text-xs font-black text-amber-300 transition hover:bg-[#43090c]">
                <i class="fa-solid fa-play"></i> Start Attendance
            </a>
        </div>
    </header>

    <main class="mx-auto w-full max-w-[1700px] space-y-4 p-4 sm:p-5 lg:p-6">
        <?php if(session('success')): ?>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-800"><?php echo e(session('success')); ?></div>
        <?php endif; ?>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
            <div class="flex items-center gap-2 bg-[#590d0d] px-4 py-2.5 text-xs font-black text-amber-300">
                <i class="fa-solid fa-filter"></i> Attendance Filters
            </div>
            <form id="teacher-attendance-filters" method="GET" action="<?php echo e(route('teacher.attendance')); ?>" class="grid grid-cols-1 gap-3 p-3 sm:grid-cols-2 xl:grid-cols-6 xl:items-end">
                <label class="text-[11px] font-black text-slate-700">
                    Attendance type
                    <select id="attendance-report-by" name="report_by" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 focus:border-amber-500 focus:outline-none">
                        <?php if($advisorySections->isNotEmpty()): ?>
                            <option value="advisory" <?php if($reportBy === 'advisory'): echo 'selected'; endif; ?>>Advisory</option>
                        <?php endif; ?>
                        <?php if($myClasses->isNotEmpty()): ?>
                            <option value="subject" <?php if($reportBy === 'subject'): echo 'selected'; endif; ?>>Subject class</option>
                        <?php endif; ?>
                        <?php if($advisorySections->isEmpty() && $myClasses->isEmpty()): ?>
                            <option value="advisory" selected>No assigned classes</option>
                        <?php endif; ?>
                    </select>
                </label>

                <?php if($advisorySections->isNotEmpty()): ?>
                    <label id="advisory-section-filter" class="text-[11px] font-black text-slate-700 <?php echo e($reportBy === 'advisory' ? '' : 'hidden'); ?>">
                        Advisory section
                        <select name="section_id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 focus:border-amber-500 focus:outline-none">
                            <?php $__currentLoopData = $advisorySections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($section->id); ?>" <?php if((string) $selectedId === (string) $section->id): echo 'selected'; endif; ?>>Grade <?php echo e($section->grade_level); ?> · <?php echo e($section->section_name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </label>
                <?php endif; ?>

                <?php if($myClasses->isNotEmpty()): ?>
                    <label id="subject-class-filter" class="text-[11px] font-black text-slate-700 <?php echo e($reportBy === 'subject' ? '' : 'hidden'); ?>">
                        Subject class
                        <select name="schedule_id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 focus:border-amber-500 focus:outline-none">
                            <?php $__currentLoopData = $myClasses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($class->id); ?>" <?php if((string) $selectedId === (string) $class->id): echo 'selected'; endif; ?>>
                                    <?php echo e($class->academicSection?->section_name ?: $class->section ?: 'Section'); ?> · <?php echo e($class->subject_name ?: $class->subjectRecord?->name ?: $class->subject_code ?: 'Subject'); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </label>
                <?php endif; ?>

                <label class="text-[11px] font-black text-slate-700">
                    Report period
                    <select id="attendance-period" name="period" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 focus:border-amber-500 focus:outline-none">
                        <option value="daily" <?php if($period === 'daily'): echo 'selected'; endif; ?>>Today / Daily</option>
                        <option value="weekly" <?php if($period === 'weekly'): echo 'selected'; endif; ?>>Weekly</option>
                        <option value="monthly" <?php if($period === 'monthly'): echo 'selected'; endif; ?>>Monthly</option>
                    </select>
                </label>

                <label class="text-[11px] font-black text-slate-700">
                    <span id="period-value-label"><?php echo e($period === 'weekly' ? 'Select week' : ($period === 'monthly' ? 'Select month' : 'Select date')); ?></span>
                    <input id="attendance-period-value" name="period_value" type="<?php echo e($period === 'weekly' ? 'week' : ($period === 'monthly' ? 'month' : 'date')); ?>" value="<?php echo e($periodValue); ?>" data-today="<?php echo e($today); ?>" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 focus:border-amber-500 focus:outline-none">
                </label>

                <label class="text-[11px] font-black text-slate-700">
                    Status
                    <select name="status" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 focus:border-amber-500 focus:outline-none">
                        <option value="">All statuses</option>
                        <option value="ON-TIME" <?php if(request('status') === 'ON-TIME'): echo 'selected'; endif; ?>>Present / on time</option>
                        <option value="LATE" <?php if(request('status') === 'LATE'): echo 'selected'; endif; ?>>Late</option>
                        <option value="ABSENT" <?php if(request('status') === 'ABSENT'): echo 'selected'; endif; ?>>Absent</option>
                    </select>
                </label>

                <div class="flex flex-wrap items-center gap-2 xl:col-span-6">
                    <label class="min-w-[180px] flex-1 text-[11px] font-black text-slate-700 sm:max-w-xs">
                        Find student
                        <input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Name or ID number" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 placeholder:text-slate-400 focus:border-amber-500 focus:outline-none">
                    </label>
                    <label class="w-28 text-[11px] font-black text-slate-700">
                        Rows
                        <select name="per_page" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 focus:border-amber-500 focus:outline-none">
                            <?php $__currentLoopData = [10, 20, 50]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($count); ?>" <?php if((int) request('per_page', 20) === $count): echo 'selected'; endif; ?>><?php echo e($count); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </label>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-[#590d0d] px-4 py-2.5 text-xs font-black text-white transition hover:bg-[#43090c]">
                        <i class="fa-solid fa-magnifying-glass"></i> Apply
                    </button>
                    <a href="<?php echo e(route('teacher.attendance')); ?>" class="rounded-lg border border-slate-300 px-3 py-2.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50">Reset</a>
                    <?php if($selectedId): ?>
                        <a href="<?php echo e(route('teacher.attendance.export', request()->query())); ?>" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-3 py-2.5 text-xs font-black text-white transition hover:bg-emerald-800">
                            <i class="fa-solid fa-file-arrow-down"></i> Export CSV
                        </a>
                    <?php endif; ?>
                </div>
                <?php if($errors->any()): ?>
                    <p class="text-xs font-bold text-red-700 xl:col-span-6"><?php echo e($errors->first()); ?></p>
                <?php endif; ?>
            </form>
        </section>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <?php $__currentLoopData = [
                ['label' => 'Students in class', 'value' => $summary['students'], 'icon' => 'fa-users', 'color' => 'blue'],
                ['label' => 'Attendance records', 'value' => $summary['total'], 'icon' => 'fa-clipboard-check', 'color' => 'slate'],
                ['label' => 'Present / on time', 'value' => $summary['present'], 'icon' => 'fa-user-check', 'color' => 'emerald'],
                ['label' => 'Late / clocked out', 'value' => $summary['late'] . ' / ' . $summary['clocked_out'], 'icon' => 'fa-clock', 'color' => 'amber'],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 shadow-xs">
                    <div class="min-w-0">
                        <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-500"><?php echo e($card['label']); ?></p>
                        <p class="mt-0.5 text-xl font-black leading-tight text-slate-900"><?php echo e(is_numeric($card['value']) ? number_format((int) $card['value']) : $card['value']); ?></p>
                    </div>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-<?php echo e($card['color']); ?>-50 text-<?php echo e($card['color']); ?>-700"><i class="fa-solid <?php echo e($card['icon']); ?>"></i></span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                <div>
                    <h2 class="text-sm font-black text-slate-900"><?php echo e($selectedLabel); ?></h2>
                    <p class="mt-0.5 text-[10px] font-semibold text-slate-500">
                        <?php echo e($period === 'daily' ? 'Daily log' : ($period === 'weekly' ? 'Weekly logs' : 'Monthly logs')); ?> · <?php echo e($periodLabel); ?>

                    </p>
                </div>
                <p class="text-[10px] font-semibold text-slate-500">P = present · L = late · — = no log, not marked absent · times are in / out</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-[11px]">
                    <thead class="bg-[#590d0d] text-[9px] font-black uppercase tracking-wide text-amber-200">
                        <tr>
                            <th class="sticky left-0 z-10 min-w-52 bg-[#590d0d] px-3 py-2.5">Student</th>
                            <?php if($period === 'daily'): ?>
                                <th class="min-w-20 px-2 py-2.5">Status</th>
                                <th class="min-w-20 px-2 py-2.5">Time in</th>
                                <th class="min-w-20 px-2 py-2.5">Time out</th>
                            <?php else: ?>
                                <?php $__currentLoopData = $dates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <th class="min-w-[76px] px-1.5 py-2 text-center">
                                        <?php echo e($date->format('d')); ?>

                                        <span class="block text-[8px] font-semibold text-amber-100/70"><?php echo e($date->format('D')); ?></span>
                                    </th>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php $__empty_1 = true; $__currentLoopData = $recentLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-amber-50/40">
                                <td class="sticky left-0 bg-white px-3 py-2 align-top">
                                    <p class="font-black text-slate-800"><?php echo e($row->student->last_name); ?>, <?php echo e($row->student->first_name); ?></p>
                                    <p class="text-[9px] text-slate-500"><?php echo e($row->student->id_number ?: 'No ID'); ?> · <?php echo e($row->student->section ?: '—'); ?></p>
                                </td>
                                <?php if($period === 'daily'): ?>
                                    <?php ($log = $row->logs[$dates->first()->toDateString()] ?? null); ?>
                                    <?php ($status = strtoupper((string) ($log->status ?? ''))); ?>
                                    <td class="px-2 py-2">
                                        <?php if($log): ?>
                                            <span class="rounded-md px-2 py-1 text-[9px] font-black <?php echo e(in_array($status, ['ON-TIME', 'PRESENT']) ? 'bg-emerald-50 text-emerald-700' : ($status === 'LATE' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700')); ?>"><?php echo e($log->status); ?></span>
                                        <?php else: ?>
                                            <span class="text-slate-300">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-2 py-2 font-bold text-slate-700"><?php echo e($log?->time_in ? \Carbon\Carbon::parse($log->time_in)->format('g:i A') : '—'); ?></td>
                                    <td class="px-2 py-2 font-bold text-slate-700"><?php echo e($log?->time_out ? \Carbon\Carbon::parse($log->time_out)->format('g:i A') : '—'); ?></td>
                                <?php else: ?>
                                    <?php $__currentLoopData = $dates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php ($log = $row->logs[$date->toDateString()] ?? null); ?>
                                        <?php ($status = strtoupper((string) ($log->status ?? ''))); ?>
                                        <td class="px-1 py-1.5 text-center align-top">
                                            <?php if($log): ?>
                                                <span title="<?php echo e($log->status); ?>" class="mx-auto inline-flex h-5 w-5 items-center justify-center rounded text-[9px] font-black <?php echo e(in_array($status, ['ON-TIME', 'PRESENT']) ? 'bg-emerald-100 text-emerald-700' : ($status === 'LATE' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700')); ?>"><?php echo e(in_array($status, ['ON-TIME', 'PRESENT']) ? 'P' : ($status === 'LATE' ? 'L' : 'A')); ?></span>
                                                <span class="mt-1 block whitespace-nowrap text-[8px] font-semibold leading-tight text-slate-600"><?php echo e($log->time_in ? \Carbon\Carbon::parse($log->time_in)->format('g:i') : '—'); ?> / <?php echo e($log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('g:i') : '—'); ?></span>
                                            <?php else: ?>
                                                <span class="text-slate-300">—</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="<?php echo e($period === 'daily' ? 4 : $dates->count() + 1); ?>" class="px-4 py-12 text-center text-slate-400">
                                    <i class="fa-regular fa-folder-open mb-2 text-xl text-slate-300"></i>
                                    <p class="font-bold">No students or attendance logs match these filters.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <footer class="flex flex-col justify-between gap-2 border-t border-slate-100 px-4 py-2.5 text-[10px] text-slate-500 sm:flex-row sm:items-center">
                <span>Showing <?php echo e($recentLogs->firstItem() ?? 0); ?>–<?php echo e($recentLogs->lastItem() ?? 0); ?> of <?php echo e($recentLogs->total()); ?> students</span>
                <?php if($recentLogs->hasPages()): ?>
                    <div class="[&_*]:!mb-0"><?php echo e($recentLogs->links()); ?></div>
                <?php endif; ?>
            </footer>
        </section>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views\teacher\attendance.blade.php ENDPATH**/ ?>