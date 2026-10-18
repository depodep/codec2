<?php if (! ($botEnabled)): ?>
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900">
        Telegram commands and outgoing notifications are disabled. Set TELEGRAM_BOT_ENABLED=true only if the bot should be reactivated.
    </div>
<?php endif; ?>

<section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Telegram summary">
    <?php $__currentLoopData = [
        ['Telegram parent accounts', number_format((int) ($linkCounts->parents ?? 0)), 'fa-user-group', 'bg-[#590d0d]', 'text-amber-300'],
        ['Student link registrations', number_format((int) ($linkCounts->registrations ?? 0)), 'fa-link', 'bg-indigo-800', 'text-indigo-200'],
        ['Approved links', number_format((int) ($linkCounts->approved ?? 0)), 'fa-circle-check', 'bg-emerald-800', 'text-emerald-200'],
        ['Pending approvals', number_format((int) ($linkCounts->pending ?? 0)), 'fa-hourglass-half', 'bg-amber-500', 'text-amber-950'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value, $icon, $headerColor, $iconColor]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex min-h-10 items-center justify-between gap-2 px-3 py-2 <?php echo e($headerColor); ?>">
                <h2 class="text-[10px] font-black uppercase leading-tight tracking-wide text-white"><?php echo e($label); ?></h2>
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/15 <?php echo e($iconColor); ?>"><i class="fa-solid <?php echo e($icon); ?>"></i></span>
            </div>
            <div class="px-3 py-2 text-xl font-black leading-none text-slate-900"><?php echo e($value); ?></div>
        </article>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</section>

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-amber-300">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <div>
                <h2 class="text-base font-black text-slate-900">Student Link Codes</h2>
                <p class="mt-0.5 text-xs font-semibold text-slate-500">
                    <?php echo e(number_format($totalStudentCodes)); ?> school-issued codes · Share each code with the matching student’s guardian.
                </p>
            </div>
        </div>
        <a href="<?php echo e(route('admin.telegram.student-link-codes.export', request()->query())); ?>"
           class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#8b1818] px-4 py-2.5 text-xs font-black text-white transition hover:bg-[#731414]">
            <i class="fa-solid fa-file-csv"></i> Export Codes CSV
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">#</th><th class="px-4 py-3">Student</th><th class="px-4 py-3">LRN</th>
                    <th class="px-4 py-3">Grade / Section</th><th class="px-4 py-3">School Code</th><th class="px-4 py-3">Telegram</th><th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php $__empty_1 = true; $__currentLoopData = $studentCodes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $studentCode): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-black text-slate-400"><?php echo e(($studentCodes->firstItem() ?? 1) + $loop->index); ?></td>
                        <td class="px-4 py-3 font-semibold text-slate-900"><?php echo e($studentCode->first_name); ?> <?php echo e($studentCode->last_name); ?></td>
                        <td class="px-4 py-3 font-mono text-slate-600"><?php echo e($studentCode->id_number); ?></td>
                        <td class="px-4 py-3 text-slate-700">
                            Grade <?php echo e($studentCode->grade_level ?: '—'); ?> · <?php echo e($studentCode->section ?: 'Unassigned'); ?>

                        </td>
                        <td class="px-4 py-3 font-mono font-black tracking-wide text-slate-800"><?php echo e($studentCode->code); ?></td>
                        <td class="px-4 py-3">
                            <?php if($studentCode->has_telegram_link): ?>
                                <span class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-[10px] font-black uppercase text-emerald-800">Linked</span>
                            <?php else: ?>
                                <span class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-black uppercase text-slate-600">Not linked</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <button type="button" data-regenerate-code="<?php echo e($studentCode->student_id); ?>"
                                    data-student-name="<?php echo e(trim($studentCode->first_name.' '.$studentCode->last_name)); ?>"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-900 hover:bg-amber-100">
                                <i class="fa-solid fa-rotate"></i> Regenerate
                            </button>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No student link codes match the selected filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="telegram-pagination border-t border-slate-100 px-5 py-4"><?php echo e($studentCodes->links()); ?></div>
</section>

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-4 border-b border-slate-200 p-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-amber-300">
                <i class="fa-solid fa-link"></i>
            </div>
            <div>
                <h2 class="text-base font-black text-slate-900">Parent Links</h2>
                <p class="mt-0.5 text-xs font-semibold text-slate-500">
                    <?php echo e($links->total()); ?> matching registration(s) · Bot <?php echo e($botEnabled ? 'enabled' : 'disabled'); ?> ·
                    Token <?php echo e($botConfigured ? 'configured' : 'not configured/disabled'); ?> ·
                    Webhook secret <?php echo e($webhookConfigured ? 'configured' : 'not configured'); ?>

                </p>
            </div>
        </div>
        <form method="GET" action="<?php echo e(route('admin.telegram.index')); ?>" id="telegram-link-filters" class="flex flex-wrap items-center gap-2">
            <div class="relative min-w-[220px] flex-1 lg:flex-none">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                <input name="search" value="<?php echo e($search); ?>" placeholder="Search name, LRN, or chat ID" aria-label="Search name, LRN, or chat ID"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-3 text-xs font-semibold focus:border-[#8b1818] focus:bg-white focus:outline-none">
            </div>
            <select name="grade" aria-label="Filter by grade" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-bold text-slate-700 focus:border-[#8b1818] focus:outline-none">
                <option value="">All Grades</option>
                <?php $__currentLoopData = $gradeLevels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $gradeLevel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($gradeLevel); ?>" <?php if((string) $grade === (string) $gradeLevel): echo 'selected'; endif; ?>>Grade <?php echo e($gradeLevel); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <select name="section" aria-label="Filter by section" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-bold text-slate-700 focus:border-[#8b1818] focus:outline-none">
                <option value="">All Sections</option>
                <?php $__currentLoopData = $sections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sectionOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($sectionOption->section_name); ?>" <?php if($section === $sectionOption->section_name): echo 'selected'; endif; ?>>
                        <?php echo e($sectionOption->section_name); ?> · Grade <?php echo e($sectionOption->grade_level); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <select name="teacher" aria-label="Filter by teacher" class="max-w-56 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-bold text-slate-700 focus:border-[#8b1818] focus:outline-none">
                <option value="">All Teachers</option>
                <?php $__currentLoopData = $teachers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $teacher): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($teacher->id); ?>" <?php if($teacherId === (int) $teacher->id): echo 'selected'; endif; ?>>
                        <?php echo e(trim($teacher->first_name.' '.$teacher->last_name)); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php if($search !== '' || $grade !== '' || $section !== '' || $teacherId > 0): ?>
                <button type="button" id="clear-telegram-filters" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-black text-slate-600 hover:bg-slate-50">Clear</button>
            <?php endif; ?>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-white text-[10px] font-black uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">#</th><th class="px-4 py-3">Student</th><th class="px-4 py-3">LRN</th>
                    <th class="px-4 py-3">Telegram chat</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Alerts</th><th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php $__empty_1 = true; $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-black text-slate-400"><?php echo e(($links->firstItem() ?? 1) + $loop->index); ?></td>
                        <td class="px-4 py-3 font-semibold text-slate-900"><?php echo e($link->first_name); ?> <?php echo e($link->last_name); ?></td>
                        <td class="px-4 py-3 font-mono text-slate-600"><?php echo e($link->id_number); ?></td>
                        <td class="px-4 py-3 font-mono text-slate-600"><?php echo e($link->telegram_chat_id); ?></td>
                        <td class="px-4 py-3 capitalize"><?php echo e($link->status); ?></td>
                        <td class="px-4 py-3"><?php echo e($link->notifications_enabled ? 'On' : 'Off'); ?></td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <?php if($link->status === 'pending'): ?>
                                    <form method="POST" action="<?php echo e(route('admin.telegram.links.approve', $link->id)); ?>" data-telegram-ajax>
                                        <?php echo csrf_field(); ?><button class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white">Approve</button>
                                    </form>
                                <?php elseif($link->status === 'approved'): ?>
                                    <form method="POST" action="<?php echo e(route('admin.telegram.links.toggle-notifications', $link->id)); ?>" data-telegram-ajax>
                                        <?php echo csrf_field(); ?><button class="rounded bg-slate-700 px-3 py-1.5 text-xs font-bold text-white"><?php echo e($link->notifications_enabled ? 'Turn alerts off' : 'Turn alerts on'); ?></button>
                                    </form>
                                <?php endif; ?>
                                <?php if($link->status !== 'revoked'): ?>
                                    <form method="POST" action="<?php echo e(route('admin.telegram.links.revoke', $link->id)); ?>" data-telegram-ajax data-confirm="Revoke this Telegram link?">
                                        <?php echo csrf_field(); ?><button class="rounded bg-rose-700 px-3 py-1.5 text-xs font-bold text-white">Revoke</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No Telegram parent links found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="telegram-pagination border-t border-slate-100 px-5 py-4"><?php echo e($links->links()); ?></div>
</section>

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center gap-3 border-b border-slate-200 p-5">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-amber-300">
            <i class="fa-solid fa-paper-plane"></i>
        </div>
        <div>
            <h2 class="text-base font-black text-slate-900">Attendance Notification History</h2>
            <p class="mt-0.5 text-xs font-semibold text-slate-500"><?php echo e($notifications->total()); ?> delivery record(s)</p>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-white text-[10px] font-black uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3">#</th><th class="px-4 py-3">Time</th><th class="px-4 py-3">Student</th><th class="px-4 py-3">Telegram chat</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Details</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-black text-slate-400"><?php echo e(($notifications->firstItem() ?? 1) + $loop->index); ?></td>
                        <td class="px-4 py-3"><?php echo e($notification->created_at); ?></td>
                        <td class="px-4 py-3"><?php echo e($notification->first_name); ?> <?php echo e($notification->last_name); ?></td>
                        <td class="px-4 py-3 font-mono"><?php echo e($notification->telegram_chat_id ?? '—'); ?></td>
                        <td class="px-4 py-3 capitalize"><?php echo e($notification->status); ?></td>
                        <td class="px-4 py-3"><?php echo e($notification->details ?? '—'); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No Telegram attendance alerts recorded.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="telegram-pagination border-t border-slate-100 px-5 py-4"><?php echo e($notifications->links()); ?></div>
</section>
<?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views/admin/telegram/partials/content.blade.php ENDPATH**/ ?>