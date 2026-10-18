<?php $__env->startSection('title', 'Evaluation History - SIATRACK Admin'); ?>

<?php $__env->startSection('content'); ?>
<div class="w-full min-h-screen bg-slate-100/60 pb-20 font-sans">
    
    <!-- Top Bar Navigation -->
    <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-6 sm:px-10 py-4 sticky top-0 z-30 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-[#8b1818] to-[#590d0d] text-white flex items-center justify-center text-lg shadow-md shadow-red-950/20 shrink-0">
                <i class="fa-solid fa-clock-rotate-left text-amber-300"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">SIA Evaluation History</h1>
                <p class="text-xs text-slate-500 font-semibold">Archived and active evaluation cycles, evaluator responses, and history</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <a href="<?php echo e(route('admin.evaluations.periods')); ?>" class="px-4 py-2.5 rounded-xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider transition shadow-md shadow-red-950/20 flex items-center gap-2">
                <i class="fa-solid fa-plus text-amber-300 text-xs"></i>
                <span>Start New Evaluation</span>
            </a>
            <a href="<?php echo e(route('admin.evaluations.results')); ?>" class="px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-black text-xs uppercase tracking-wider transition border border-slate-300 shadow-2xs flex items-center gap-2">
                <i class="fa-solid fa-chart-pie text-slate-500 text-xs"></i>
                <span>View All Analytics</span>
            </a>
        </div>
    </header>

    <main class="max-w-[1700px] mx-auto pt-6 px-4 sm:px-6 lg:px-8 space-y-6">
        
        <?php if(session('success')): ?>
            <div class="p-4 bg-emerald-50 border-2 border-emerald-300 text-emerald-900 text-xs font-bold rounded-2xl flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                    <span><?php echo e(session('success')); ?></span>
                </div>
                <button @click="$el.parentElement.remove()" class="text-emerald-700 hover:text-emerald-950"><i class="fa-solid fa-xmark"></i></button>
            </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
            <div class="p-4 bg-rose-50 border-2 border-rose-300 text-rose-900 text-xs font-bold rounded-2xl flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-base shrink-0"></i>
                    <span><?php echo e(session('error')); ?></span>
                </div>
                <button @click="$el.parentElement.remove()" class="text-rose-700 hover:text-rose-950"><i class="fa-solid fa-xmark"></i></button>
            </div>
        <?php endif; ?>

        <!-- Filter & Search Bar -->
        <div class="bg-white border-2 border-slate-200/90 rounded-3xl p-5 space-y-3 shadow-xs">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <span class="text-xs font-black uppercase text-slate-900 tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-filter text-[#8b1818]"></i>
                    <span>History Search & Filters</span>
                </span>
                <?php if(!empty($search) || !empty($schoolYear) || !empty($status) || !empty($formType) || !empty($section)): ?>
                    <a href="<?php echo e(route('admin.evaluations.history')); ?>" class="text-[11px] font-bold text-rose-600 hover:underline">Clear Filters</a>
                <?php endif; ?>
            </div>

            <form method="GET" action="<?php echo e(route('admin.evaluations.history')); ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Search Input -->
                <div class="sm:col-span-2 relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="<?php echo e($search ?? ''); ?>" placeholder="Search by Evaluation Name..." class="w-full pl-9 pr-4 py-2 text-xs font-bold border-2 border-slate-200 rounded-xl outline-none focus:border-[#8b1818]">
                </div>

                <!-- School Year Filter -->
                <div>
                    <select name="school_year" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs font-bold border-2 border-slate-200 rounded-xl outline-none focus:border-[#8b1818] bg-white">
                        <option value="">All School Years</option>
                        <?php $__currentLoopData = $schoolYears ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($sy); ?>" <?php echo e(($schoolYear ?? '') === $sy ? 'selected' : ''); ?>>SY <?php echo e($sy); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <!-- Evaluator Form Type Filter -->
                <div>
                    <select name="form_type" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs font-bold border-2 border-slate-200 rounded-xl outline-none focus:border-[#8b1818] bg-white">
                        <option value="">All Evaluator Forms</option>
                        <option value="principal" <?php echo e(($formType ?? '') === 'principal' ? 'selected' : ''); ?>>Principal's Evaluation</option>
                        <option value="peer" <?php echo e(($formType ?? '') === 'peer' ? 'selected' : ''); ?>>Peer Evaluation</option>
                        <option value="student" <?php echo e(($formType ?? '') === 'student' ? 'selected' : ''); ?>>Student Evaluation</option>
                        <option value="self" <?php echo e(($formType ?? '') === 'self' ? 'selected' : ''); ?>>Self Evaluation</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs font-bold border-2 border-slate-200 rounded-xl outline-none focus:border-[#8b1818] bg-white">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo e(($status ?? '') === 'active' ? 'selected' : ''); ?>>Active</option>
                        <option value="completed" <?php echo e(($status ?? '') === 'completed' ? 'selected' : ''); ?>>Completed</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- History Table with Non-shrinking Columns -->
        <div class="bg-white border-2 border-slate-200/90 rounded-3xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1100px]">
                    <thead>
                        <tr class="bg-slate-50 border-b-2 border-slate-200 text-[11px] font-black uppercase tracking-wider text-slate-600">
                            <th class="py-4 px-6 text-center w-12 whitespace-nowrap">#</th>
                            <th class="py-4 px-6 whitespace-nowrap">Evaluation Name</th>
                            <th class="py-4 px-6 whitespace-nowrap">Evaluation Window</th>
                            <th class="py-4 px-6 whitespace-nowrap">Respondent Submissions</th>
                            <th class="py-4 px-6 text-center whitespace-nowrap">Total Responses</th>
                            <th class="py-4 px-6 text-center whitespace-nowrap">Status</th>
                            <th class="py-4 px-6 text-right whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-bold text-slate-700">
                        <?php $__empty_1 = true; $__currentLoopData = $cycles ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-4 px-6 text-center text-slate-400 font-mono whitespace-nowrap"><?php echo e($index + 1); ?></td>
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 whitespace-nowrap">
                                            <span class="text-sm font-black text-slate-900"><?php echo e($c->name); ?></span>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 shrink-0 whitespace-nowrap">
                                                SY <?php echo e($c->school_year); ?>

                                            </span>
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-semibold block">Created <?php echo e(\Carbon\Carbon::parse($c->created_at)->format('M d, Y')); ?></span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 text-slate-800 whitespace-nowrap">
                                        <i class="fa-regular fa-calendar-days text-slate-400 text-xs shrink-0"></i>
                                        <span class="whitespace-nowrap"><?php echo e(\Carbon\Carbon::parse($c->start_date)->format('M d, Y')); ?> &ndash; <?php echo e(\Carbon\Carbon::parse($c->end_date)->format('M d, Y')); ?></span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="grid grid-cols-2 gap-1 w-fit">
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200 whitespace-nowrap" title="Student Submissions">
                                            Student: <?php echo e($c->student_count ?? 0); ?>

                                        </span>
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-extrabold bg-blue-50 text-blue-800 border border-blue-200 whitespace-nowrap" title="Peer Submissions">
                                            Peer: <?php echo e($c->peer_count ?? 0); ?>

                                        </span>
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-extrabold bg-amber-50 text-amber-800 border border-amber-200 whitespace-nowrap" title="Self Submissions">
                                            Self: <?php echo e($c->self_count ?? 0); ?>

                                        </span>
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-extrabold bg-purple-50 text-purple-800 border border-purple-200 whitespace-nowrap" title="Principal Submissions">
                                            Principal: <?php echo e($c->principal_count ?? 0); ?>

                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center whitespace-nowrap">
                                    <span class="px-3 py-1 rounded-full text-xs font-black bg-slate-900 text-white shadow-2xs">
                                        <?php echo e($c->response_count ?? 0); ?> Total
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center whitespace-nowrap">
                                    <?php if($c->status === 'active'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-slate-100 text-slate-700 border border-slate-300">
                                            <i class="fa-solid fa-lock text-[9px] text-slate-500"></i>
                                            Completed
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-right whitespace-nowrap space-x-2">
                                    <a href="<?php echo e(route('admin.evaluations.results', ['cycle_id' => $c->id])); ?>" class="px-3.5 py-2 rounded-xl bg-[#8b1818] hover:bg-[#731414] text-white font-bold text-xs inline-flex items-center gap-1.5 transition shadow-xs">
                                        <i class="fa-solid fa-chart-pie text-amber-300 text-[11px]"></i>
                                        <span>View Analytics</span>
                                    </a>


                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400 font-bold">
                                    <i class="fa-solid fa-folder-open text-3xl block mb-2 text-slate-300"></i>
                                    No evaluation history records found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views\admin\evaluations\history.blade.php ENDPATH**/ ?>