

<?php $__env->startSection('title', 'Telegram Parent Notifications - SIATRACK'); ?>

<?php $__env->startSection('content'); ?>
<main class="min-h-screen bg-slate-50 p-6 lg:p-10 space-y-6">
    <header>
        <h1 class="text-2xl font-black text-slate-900">Telegram Parent Notifications</h1>
        <p class="mt-1 text-sm text-slate-600">Review parent link requests, manage approved access, and inspect attendance alert delivery.</p>
    </header>

    <?php if(session('success')): ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if($errors->any()): ?>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
        <h2 class="text-lg font-black text-slate-900">Bot connection</h2>
        <p class="text-sm text-slate-600">
            Bot token: <strong><?php echo e($botConfigured ? 'Configured in server environment' : 'Not configured'); ?></strong>.
            Webhook secret: <strong><?php echo e($webhookConfigured ? 'Configured' : 'Not configured'); ?></strong>.
            The bot token is never displayed or stored in the application database.
        </p>
        <div class="flex flex-wrap items-end gap-4">
            <form method="POST" action="<?php echo e(route('admin.telegram.test-connection')); ?>">
                <?php echo csrf_field(); ?>
                <button class="rounded-lg bg-[#590d0d] px-4 py-2 text-sm font-bold text-white">Test bot connection</button>
            </form>
            <form method="POST" action="<?php echo e(route('admin.telegram.test-notification')); ?>" class="flex flex-wrap items-end gap-2">
                <?php echo csrf_field(); ?>
                <label class="text-xs font-bold text-slate-700">
                    Private chat ID
                    <input name="chat_id" inputmode="numeric" required pattern="[0-9]{1,20}" class="mt-1 block rounded-lg border border-slate-300 px-3 py-2 text-sm" value="<?php echo e(old('chat_id')); ?>">
                </label>
                <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-bold text-white">Send test notification</button>
            </form>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-black text-slate-900">Parent links</h2>
            <form method="GET" class="flex gap-2">
                <input name="search" value="<?php echo e($search); ?>" placeholder="Search student or chat ID" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-bold text-white">Search</button>
            </form>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr><th class="py-2 pr-4">Student</th><th class="py-2 pr-4">LRN</th><th class="py-2 pr-4">Telegram chat</th><th class="py-2 pr-4">Status</th><th class="py-2 pr-4">Alerts</th><th class="py-2">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php $__empty_1 = true; $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="py-3 pr-4 font-semibold"><?php echo e($link->first_name); ?> <?php echo e($link->last_name); ?></td>
                            <td class="py-3 pr-4 font-mono"><?php echo e($link->id_number); ?></td>
                            <td class="py-3 pr-4 font-mono"><?php echo e($link->telegram_chat_id); ?></td>
                            <td class="py-3 pr-4 capitalize"><?php echo e($link->status); ?></td>
                            <td class="py-3 pr-4"><?php echo e($link->notifications_enabled ? 'On' : 'Off'); ?></td>
                            <td class="py-3">
                                <div class="flex flex-wrap gap-2">
                                    <?php if($link->status === 'pending'): ?>
                                        <form method="POST" action="<?php echo e(route('admin.telegram.links.approve', $link->id)); ?>"><?php echo csrf_field(); ?><button class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white">Approve</button></form>
                                    <?php elseif($link->status === 'approved'): ?>
                                        <form method="POST" action="<?php echo e(route('admin.telegram.links.toggle-notifications', $link->id)); ?>"><?php echo csrf_field(); ?><button class="rounded bg-slate-700 px-3 py-1.5 text-xs font-bold text-white"><?php echo e($link->notifications_enabled ? 'Turn alerts off' : 'Turn alerts on'); ?></button></form>
                                    <?php endif; ?>
                                    <?php if($link->status !== 'revoked'): ?>
                                        <form method="POST" action="<?php echo e(route('admin.telegram.links.revoke', $link->id)); ?>"><?php echo csrf_field(); ?><button class="rounded bg-rose-700 px-3 py-1.5 text-xs font-bold text-white" onclick="return confirm('Revoke this Telegram link?')">Revoke</button></form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="6" class="py-6 text-center text-slate-500">No Telegram parent links found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4"><?php echo e($links->links()); ?></div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-black text-slate-900">Attendance notification history</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr><th class="py-2 pr-4">Time</th><th class="py-2 pr-4">Student</th><th class="py-2 pr-4">Telegram chat</th><th class="py-2 pr-4">Status</th><th class="py-2">Details</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="py-3 pr-4"><?php echo e($notification->created_at); ?></td>
                            <td class="py-3 pr-4"><?php echo e($notification->first_name); ?> <?php echo e($notification->last_name); ?></td>
                            <td class="py-3 pr-4 font-mono"><?php echo e($notification->telegram_chat_id ?? '—'); ?></td>
                            <td class="py-3 pr-4 capitalize"><?php echo e($notification->status); ?></td>
                            <td class="py-3"><?php echo e($notification->details ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5" class="py-6 text-center text-slate-500">No Telegram attendance alerts recorded.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4"><?php echo e($notifications->links()); ?></div>
    </section>
</main>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views\admin\telegram\index.blade.php ENDPATH**/ ?>