<?php $__env->startSection('content'); ?>
<div class="p-6">
    <!-- Header Title -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-black text-slate-900">User Management</h1>
            <p class="text-xs text-slate-500 font-semibold mt-0.5">Manage institutional student, faculty, management, and administrator directory records</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?php echo e(route('admin.users.export')); ?>" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition flex items-center gap-2 shadow-xs">
                <i class="fa-solid fa-file-excel"></i> Export CSV
            </a>
            <a href="<?php echo e(route('admin.users.create')); ?>" class="px-4 py-2 bg-[#8b1818] hover:bg-opacity-90 text-white text-xs font-bold rounded-xl transition flex items-center gap-2 shadow-xs">
                <i class="fa-solid fa-user-plus"></i> Add New User
            </a>
        </div>
    </div>

    <!-- Search Bar & Filters Section -->
    <div class="bg-white p-4 rounded-3xl border-2 border-slate-200 shadow-xs mb-6 flex flex-col md:flex-row items-center justify-end gap-4">
        <div class="w-full md:w-80 relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-search text-xs"></i>
            </span>
            <input type="text" id="userSearch" placeholder="Search name, ID, or email..." 
                   class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#8b1818] font-semibold text-slate-800">
        </div>
    </div>

    <!-- Unified Users Table (Alphabetical Directory) -->
    <div class="bg-white rounded-3xl border-2 border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs" id="usersTable">
                <thead class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-black text-slate-400 uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">Name / Account</th>
                        <th class="py-3.5 px-6">ID Number / Username</th>
                        <th class="py-3.5 px-6">Role / Position</th>
                        <th class="py-3.5 px-6">Email / Contact</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                    <?php $__empty_1 = true; $__currentLoopData = $users ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-slate-50/80 transition user-row">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-[#8b1818] text-white text-xs font-black flex items-center justify-center shrink-0 shadow-2xs">
                                        <?php echo e(strtoupper(substr($user->first_name ?? 'U', 0, 1))); ?><?php echo e(strtoupper(substr($user->last_name ?? 'S', 0, 1))); ?>

                                    </div>
                                    <div>
                                        <span class="text-slate-900 font-black block user-name"><?php echo e($user->last_name); ?>, <?php echo e($user->first_name); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 font-mono text-slate-600 font-bold user-id">
                                <?php echo e($user->id_number ?? $user->username ?? 'N/A'); ?>

                            </td>
                            <td class="py-4 px-6">
                                <?php
                                    $roleBadge = match((int)$user->role_id) {
                                        1 => ['bg' => 'bg-amber-100 text-amber-900 border-amber-300', 'label' => 'Administrator'],
                                        2 => ['bg' => 'bg-blue-100 text-blue-900 border-blue-300', 'label' => 'Faculty / Teacher'],
                                        3 => ['bg' => 'bg-emerald-100 text-emerald-900 border-emerald-300', 'label' => 'Student'],
                                        4 => ['bg' => 'bg-purple-100 text-purple-900 border-purple-300', 'label' => 'Management'],
                                        default => ['bg' => 'bg-slate-100 text-slate-800 border-slate-300', 'label' => 'User']
                                    };
                                ?>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black border uppercase <?php echo e($roleBadge['bg']); ?>">
                                    <?php echo e($roleBadge['label']); ?>

                                </span>
                            </td>
                            <td class="py-4 px-6 text-slate-600">
                                <?php echo e($user->email ?? 'No email provided'); ?>

                            </td>
                            <td class="py-4 px-6 text-right space-x-2">
                                <a href="<?php echo e(route('admin.users.index')); ?>" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 inline-flex items-center justify-center transition" title="Manage in Users">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400 font-medium">
                                <i class="fa-solid fa-users-slash text-2xl mb-2 block text-slate-300"></i>
                                No institutional accounts found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views\admin\reports\users.blade.php ENDPATH**/ ?>