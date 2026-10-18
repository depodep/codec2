

<?php $__env->startSection('title', 'Attendance Report - SIATRACK'); ?>

<?php $__env->startSection('content'); ?>
<div class="min-h-screen bg-slate-50/70">
    <header class="bg-white border-b border-slate-200 px-6 lg:px-10 py-5">
        <div class="max-w-[1500px] mx-auto flex items-center gap-4">
            <a href="<?php echo e(route('admin.reports')); ?>" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-xl lg:text-2xl font-black text-slate-900">Attendance Report</h1>
                <p class="text-xs text-slate-500 font-semibold mt-1">Advisory daily scans and subject schedule time-ins</p>
            </div>
        </div>
    </header>

    <main class="p-5 lg:p-8 max-w-[1500px] mx-auto space-y-5">
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="bg-[#590d0d] px-4 py-2.5 text-sm font-bold text-amber-300 flex items-center gap-2">
                <i class="fa-solid fa-magnifying-glass"></i> Report Filters
            </div>
            <form id="admin-attendance-report-filters" method="GET" action="<?php echo e(route('admin.reports.attendance', [], false)); ?>" class="overflow-x-auto p-4 lg:p-5">
                <div class="grid min-w-[1000px] grid-cols-7 gap-x-3 items-end">
                    <label class="text-xs font-bold text-slate-700">
                        <i class="fa-solid fa-filter mr-1"></i> Report by
                        <select name="report_by" class="mt-1 block w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 text-slate-800">
                            <option value="section" <?php if($filters['report_by'] === 'section'): echo 'selected'; endif; ?>>Section</option>
                            <option value="subject" <?php if($filters['report_by'] === 'subject'): echo 'selected'; endif; ?>>Subject</option>
                        </select>
                    </label>
                    <label class="text-xs font-bold text-slate-700">
                        <i class="fa-regular fa-calendar mr-1"></i> Report period
                        <select name="period" class="mt-1 block w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 text-slate-800">
                            <option value="daily" <?php if($filters['period'] === 'daily'): echo 'selected'; endif; ?>>Daily</option>
                            <option value="weekly" <?php if($filters['period'] === 'weekly'): echo 'selected'; endif; ?>>Weekly</option>
                            <option value="monthly" <?php if($filters['period'] === 'monthly'): echo 'selected'; endif; ?>>Monthly</option>
                        </select>
                    </label>
                    <div id="admin-report-section-filter" class="<?php echo e($filters['report_by'] === 'section' ? '' : 'hidden'); ?>">
                        <label class="text-xs font-bold text-slate-700">
                            <i class="fa-solid fa-people-group mr-1"></i> Select section
                            <select name="section" class="mt-1 block w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 text-slate-800">
                                <?php $__empty_1 = true; $__currentLoopData = $sections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <option value="<?php echo e($section->key); ?>" <?php if(($filters['section'] ?? '') === $section->key): echo 'selected'; endif; ?>>
                                        <?php echo e($section->grade_level ? 'Grade '.$section->grade_level.' · ' : ''); ?><?php echo e($section->section_name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <option value="">No sections available</option>
                                <?php endif; ?>
                            </select>
                        </label>
                    </div>
                    <div id="admin-report-subject-filter" class="<?php echo e($filters['report_by'] === 'subject' ? '' : 'hidden'); ?>">
                        <label class="text-xs font-bold text-slate-700">
                            <i class="fa-solid fa-book-open mr-1"></i> Select subject
                            <select name="schedule_id" class="mt-1 block w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 text-slate-800">
                                <?php $__empty_1 = true; $__currentLoopData = $schedules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $schedule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <option value="<?php echo e($schedule->id); ?>" <?php if((string)($filters['schedule_id'] ?? '') === (string)$schedule->id): echo 'selected'; endif; ?>>
                                        <?php echo e($schedule->subject_name ?: $schedule->catalog_subject_name ?: $schedule->subject_code ?: 'Class'); ?> · <?php echo e($schedule->section_name ?: $schedule->section ?: 'Section not set'); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <option value="">No subjects available</option>
                                <?php endif; ?>
                            </select>
                        </label>
                    </div>
                    <label class="text-xs font-bold text-slate-700">
                        <i class="fa-solid fa-calendar-day mr-1"></i>
                        <span id="admin-report-period-label"><?php echo e($filters['period'] === 'weekly' ? 'Select week' : ($filters['period'] === 'monthly' ? 'Select month' : 'Select date')); ?></span>
                        <input id="admin-report-period-value" type="<?php echo e($filters['period'] === 'weekly' ? 'week' : ($filters['period'] === 'monthly' ? 'month' : 'date')); ?>" name="period_value" value="<?php echo e($filters['period_value']); ?>" data-default-date="<?php echo e(now()->toDateString()); ?>" aria-label="<?php echo e($filters['period'] === 'weekly' ? 'Select week' : ($filters['period'] === 'monthly' ? 'Select month' : 'Select date')); ?>" class="mt-1 block w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 text-slate-800">
                    </label>
                    <label class="text-xs font-bold text-slate-700">
                        Status
                        <select name="status" class="mt-1 block w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 text-slate-800">
                            <option value="">All statuses</option>
                            <?php $__currentLoopData = ['ON-TIME' => 'Present / on time', 'LATE' => 'Late', 'ABSENT' => 'Absent']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(($filters['status'] ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </label>
                    <label class="text-xs font-bold text-slate-700">
                        Rows per page
                        <select name="per_page" class="mt-1 block w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 text-slate-800">
                            <?php $__currentLoopData = [10, 20]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($count); ?>" <?php if((int)($filters['per_page'] ?? 10) === $count): echo 'selected'; endif; ?>><?php echo e($count); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button" id="clear-attendance-filters" class="px-3 py-2 rounded-lg border border-slate-200 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold">
                            Reset
                        </button>
                    </div>
                </div>
            <?php if($errors->any()): ?>
                    <p class="mt-3 text-xs font-bold text-red-600"><?php echo e($errors->first()); ?></p>
                <?php endif; ?>
            </form>
        </div>

        <div id="attendance-report-error" class="hidden text-xs font-bold text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-3" role="alert"></div>
        <?php echo $__env->make('admin.reports.partials.attendance-results', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </main>
</div>
<script>
(() => {
    const form = document.getElementById('admin-attendance-report-filters');
    const error = document.getElementById('attendance-report-error');
    if (!form) return;
    let activeRequest;

    function toggleReportSelection() {
        const reportBy = form.elements.report_by.value;
        document.getElementById('admin-report-section-filter').classList.toggle('hidden', reportBy !== 'section');
        document.getElementById('admin-report-subject-filter').classList.toggle('hidden', reportBy !== 'subject');
    }

    function dateFromPeriodValue(period, value) {
        if (period === 'weekly') {
            const match = value.match(/^(\d{4})-W(\d{2})$/);
            if (!match) return new Date();
            const date = new Date(Number(match[1]), 0, 4);
            date.setDate(date.getDate() - ((date.getDay() + 6) % 7) + (Number(match[2]) - 1) * 7);
            return date;
        }
        if (period === 'monthly') return new Date(`${value}-01T12:00:00`);
        return new Date(`${value}T12:00:00`);
    }

    function periodValueForDate(date, period) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        if (period === 'monthly') return `${year}-${month}`;
        if (period === 'weekly') {
            const utcDate = new Date(Date.UTC(year, date.getMonth(), date.getDate()));
            const weekday = utcDate.getUTCDay() || 7;
            utcDate.setUTCDate(utcDate.getUTCDate() + 4 - weekday);
            const isoYear = utcDate.getUTCFullYear();
            const yearStart = new Date(Date.UTC(isoYear, 0, 1));
            const week = Math.ceil((((utcDate - yearStart) / 86400000) + 1) / 7);
            return `${isoYear}-W${String(week).padStart(2, '0')}`;
        }
        return `${year}-${month}-${day}`;
    }

    function syncPeriodInput(date = null) {
        const period = form.elements.period.value;
        const input = document.getElementById('admin-report-period-value');
        const previousPeriod = input.dataset.period || period;
        const selectedDate = date || dateFromPeriodValue(previousPeriod, input.value);
        input.type = period === 'weekly' ? 'week' : (period === 'monthly' ? 'month' : 'date');
        input.value = periodValueForDate(selectedDate, period);
        input.setAttribute('aria-label', period === 'weekly' ? 'Select week' : (period === 'monthly' ? 'Select month' : 'Select date'));
        document.getElementById('admin-report-period-label').textContent = period === 'weekly' ? 'Select week' : (period === 'monthly' ? 'Select month' : 'Select date');
        input.dataset.period = period;
    }

    async function refreshResults(pushState = true, targetUrl = null) {
        activeRequest?.abort();
        activeRequest = new AbortController();
        const url = targetUrl ? new URL(targetUrl, window.location.href) : new URL(form.action);
        if (!targetUrl) {
            new FormData(form).forEach((value, key) => {
                if (value !== '') url.searchParams.set(key, value);
            });
            url.searchParams.set('page', '1');
        }
        error.classList.add('hidden');
        const container = document.getElementById('attendance-report-results');
        container?.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                signal: activeRequest.signal
            });
            if (!response.ok) {
                const problem = await response.json().catch(() => ({}));
                throw new Error(Object.values(problem.errors ?? {}).flat()[0] ?? problem.message ?? 'The report could not be updated. Please try again.');
            }
            const documentFragment = new DOMParser().parseFromString(await response.text(), 'text/html');
            const updated = documentFragment.getElementById('attendance-report-results');
            if (!updated || !container) throw new Error('The report response was incomplete. Please reload the page.');
            container.replaceWith(updated);
            if (pushState) history.pushState({}, '', url);
        } catch (exception) {
            if (exception.name !== 'AbortError') {
                error.textContent = exception instanceof TypeError
                    ? 'Could not reach the attendance report. Check the server connection and reload the page.'
                    : exception.message;
                error.classList.remove('hidden');
            }
        } finally {
            document.getElementById('attendance-report-results')?.removeAttribute('aria-busy');
        }
    }

    form.addEventListener('submit', event => {
        event.preventDefault();
        refreshResults();
    });
    form.addEventListener('change', event => {
        if (event.target.name === 'report_by') {
            toggleReportSelection();
        } else if (event.target.name === 'period') {
            syncPeriodInput();
        }
        refreshResults();
    });
    document.getElementById('clear-attendance-filters').addEventListener('click', () => {
        form.querySelectorAll('[name]').forEach(field => {
            if (field.name === 'report_by') {
                field.value = 'section';
            } else if (field.name === 'period') {
                field.value = 'daily';
            } else if (field.name === 'period_value') {
                field.value = field.dataset.defaultDate;
            } else if (field.tagName === 'SELECT') {
                field.selectedIndex = 0;
            } else {
                field.value = '';
            }
        });
        toggleReportSelection();
        syncPeriodInput(new Date(`${document.getElementById('admin-report-period-value').dataset.defaultDate}T12:00:00`));
        refreshResults();
    });
    toggleReportSelection();
    syncPeriodInput(dateFromPeriodValue(form.elements.period.value, document.getElementById('admin-report-period-value').value));
    document.addEventListener('click', event => {
        const link = event.target.closest('#attendance-report-results a[href]');
        if (!link) return;
        const pageUrl = new URL(link.href, window.location.href);
        if (pageUrl.origin !== window.location.origin || pageUrl.pathname !== window.location.pathname || !pageUrl.searchParams.has('page')) return;
        event.preventDefault();
        refreshResults(true, pageUrl.href);
    });
    window.addEventListener('popstate', () => {
        const params = new URLSearchParams(window.location.search);
        form.querySelectorAll('[name]').forEach(field => {
            if (field.name === 'period_value') return;
            if (params.has(field.name)) field.value = params.get(field.name);
            else if (field.name === 'report_by') field.value = 'section';
            else if (field.name === 'period') field.value = 'daily';
            else if (field.tagName === 'SELECT') field.selectedIndex = 0;
            else field.value = '';
        });
        toggleReportSelection();
        syncPeriodInput(params.has('period_value')
            ? dateFromPeriodValue(form.elements.period.value, params.get('period_value'))
            : new Date(`${document.getElementById('admin-report-period-value').dataset.defaultDate}T12:00:00`));
        refreshResults(false, window.location.href);
    });
})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views\admin\reports\attendance.blade.php ENDPATH**/ ?>