<div id="attendance-report-results" class="space-y-4" aria-live="polite">
    <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
            <div class="flex min-w-0 flex-1 items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#590d0d]">Selected report</p>
                    <h2 class="truncate text-lg font-black text-slate-900"><?php echo e($selectedLabel ?? 'No selection'); ?></h2>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">Period</p>
                    <p class="text-sm font-bold text-slate-700"><?php echo e($periodLabel ?? '—'); ?></p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:flex-[2]">
                <?php $__currentLoopData = [
                    ['label' => 'Recorded scans', 'value' => $summary['total'], 'style' => 'slate'],
                    ['label' => 'Present / On time', 'value' => $summary['present'], 'style' => 'emerald'],
                    ['label' => 'Late', 'value' => $summary['late'], 'style' => 'amber'],
                    ['label' => 'Marked absent', 'value' => $summary['absent'], 'style' => 'rose'],
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                        <p class="text-[9px] font-black uppercase tracking-wide text-slate-400"><?php echo e($card['label']); ?></p>
                        <p class="mt-0.5 text-xl font-black text-<?php echo e($card['style']); ?>-700"><?php echo e(number_format($card['value'])); ?></p>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>

    <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-black text-sm text-slate-900">
                    <?php echo e($filters['period'] === 'daily' ? 'Daily attendance' : ($filters['period'] === 'weekly' ? 'Weekly attendance' : 'Monthly attendance')); ?>

                    · <?php echo e($filters['report_by'] === 'section' ? 'Section' : 'Subject'); ?>

                </h2>
                <p class="text-[11px] text-slate-500 mt-0.5">Only recorded attendance is shown; a missing scan is not counted as absent.</p>
            </div>
            <span class="text-[11px] font-bold text-slate-500"><?php echo e($attendanceRows->total()); ?> students</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <?php if($filters['period'] === 'daily'): ?>
                    <thead class="bg-[#590d0d] text-[10px] uppercase tracking-wide text-amber-200">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Student</th>
                            <th class="px-4 py-3">Section</th>
                            <?php if($filters['report_by'] === 'subject'): ?>
                                <th class="px-4 py-3">Subject</th>
                            <?php endif; ?>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Time in</th>
                            <th class="px-4 py-3">Time out</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php $__empty_1 = true; $__currentLoopData = $attendanceRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php ($log = $row->logs[$dates->first()->toDateString()] ?? null); ?>
                            <?php ($status = strtoupper((string) ($log->status ?? ''))); ?>
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-3 text-slate-400 font-semibold"><?php echo e($attendanceRows->firstItem() + $index); ?></td>
                                <td class="px-4 py-3">
                                    <p class="font-bold text-slate-800"><?php echo e($row->student->last_name); ?>, <?php echo e($row->student->first_name); ?></p>
                                    <p class="text-[10px] text-slate-400"><?php echo e($row->student->id_number ?: 'No ID'); ?></p>
                                </td>
                                <td class="px-4 py-3 text-slate-600"><?php echo e($row->student->section ?: '—'); ?></td>
                                <?php if($filters['report_by'] === 'subject'): ?>
                                    <td class="px-4 py-3 text-slate-600"><?php echo e($selectedLabel); ?></td>
                                <?php endif; ?>
                                <td class="px-4 py-3">
                                    <?php if($log): ?>
                                        <span class="px-2 py-1 rounded-md font-black <?php echo e(in_array($status, ['ON-TIME', 'PRESENT']) ? 'bg-emerald-50 text-emerald-700' : ($status === 'LATE' ? 'bg-amber-50 text-amber-700' : ($status === 'ABSENT' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-600'))); ?>">
                                            <?php echo e($log->status); ?>

                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-300">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 font-bold text-slate-700"><?php echo e($log?->time_in ? \Carbon\Carbon::parse($log->time_in)->format('g:i A') : '—'); ?></td>
                                <td class="px-4 py-3 font-bold text-slate-700"><?php echo e($log?->time_out ? \Carbon\Carbon::parse($log->time_out)->format('g:i A') : '—'); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="<?php echo e($filters['report_by'] === 'subject' ? 7 : 6); ?>" class="px-4 py-12 text-center text-slate-400">No students or attendance logs match these filters.</td></tr>
                        <?php endif; ?>
                    </tbody>
                <?php else: ?>
                    <thead class="bg-[#590d0d] text-amber-200 text-[10px] uppercase tracking-wide">
                        <tr>
                            <th class="px-3 py-3 whitespace-nowrap">#</th>
                            <th class="px-3 py-3 whitespace-nowrap">Student</th>
                            <?php $__currentLoopData = $dates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <th class="px-2 py-3 text-center whitespace-nowrap">
                                    <?php echo e($date->format('d')); ?>

                                    <span class="block text-[9px] font-medium text-slate-300"><?php echo e($date->format('D')); ?></span>
                                </th>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $attendanceRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="border-t border-slate-200 hover:bg-amber-50/50">
                                <td class="px-3 py-3 text-slate-400 font-semibold"><?php echo e($attendanceRows->firstItem() + $loop->index); ?></td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="font-bold text-slate-800"><?php echo e($row->student->last_name); ?>, <?php echo e($row->student->first_name); ?></div>
                                    <div class="text-[10px] text-slate-500"><?php echo e($row->student->id_number ?: 'No ID'); ?></div>
                                </td>
                                <?php $__currentLoopData = $dates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php ($log = $row->logs[$date->toDateString()] ?? null); ?>
                                    <?php ($status = strtoupper((string) ($log->status ?? ''))); ?>
                                    <td class="px-2 py-3 text-center align-middle">
                                        <?php if($log): ?>
                                            <span title="<?php echo e($log->status); ?><?php echo e($log->time_in ? ' · '.\Carbon\Carbon::parse($log->time_in)->format('g:i A') : ''); ?>" class="inline-flex items-center justify-center w-7 h-7 rounded-md text-[10px] font-black <?php echo e(in_array($status, ['ON-TIME', 'PRESENT']) ? 'bg-emerald-100 text-emerald-700' : ($status === 'LATE' ? 'bg-amber-100 text-amber-700' : ($status === 'ABSENT' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600'))); ?>">
                                                <?php echo e(in_array($status, ['ON-TIME', 'PRESENT']) ? 'P' : ($status === 'LATE' ? 'L' : ($status === 'ABSENT' ? 'A' : '—'))); ?>

                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-300">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="<?php echo e(count($dates) + 2); ?>" class="px-4 py-12 text-center text-slate-400">No students or attendance logs match these filters.</td></tr>
                        <?php endif; ?>
                    </tbody>
                <?php endif; ?>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-[11px] text-slate-500">
                Showing <?php echo e($attendanceRows->firstItem() ?? 0); ?> to <?php echo e($attendanceRows->lastItem() ?? 0); ?> of <?php echo e($attendanceRows->total()); ?> students
            </p>
            <?php if($attendanceRows->hasPages()): ?>
                <div class="[&_*]:!mb-0"><?php echo e($attendanceRows->links()); ?></div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views\admin\reports\partials\attendance-results.blade.php ENDPATH**/ ?>