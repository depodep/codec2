

<?php $__env->startSection('title', 'Principal Faculty Evaluation | SIATRACK'); ?>

<?php $__env->startSection('content'); ?>
<div class="min-h-screen bg-slate-50/70">
    <header class="flex flex-col justify-between gap-4 border-b-2 border-slate-200 bg-white px-6 py-5 lg:flex-row lg:items-center lg:px-10">
        <div>
            <h1 class="text-xl font-black text-slate-900">Principal Faculty Evaluation</h1>
            <p class="mt-1 text-xs font-semibold text-slate-500">Evaluate faculty using the school’s existing Principal Evaluation rubric.</p>
        </div>
        <a href="<?php echo e(route('management.dashboard')); ?>" class="rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-200"><i class="fa-solid fa-arrow-left mr-1"></i> Dashboard</a>
    </header>
    <main class="mx-auto max-w-6xl space-y-4 px-4 py-6 sm:px-6 lg:px-10">
        <?php if(session('success')): ?>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-bold text-emerald-800"><?php echo e(session('success')); ?></div>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-bold text-rose-800"><?php echo e(session('error')); ?></div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-800"><?php echo e($errors->first()); ?></div>
        <?php endif; ?>
        <?php if(!$activeCycle): ?>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm font-bold text-amber-900">There is no active faculty evaluation cycle. Contact an administrator to open a cycle.</div>
        <?php else: ?>
            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-xs font-bold text-blue-900">
                <span class="font-black"><?php echo e($activeCycle->name); ?></span>
                <span class="ml-2 text-blue-700"><?php echo e($activeCycle->school_year); ?></span>
            </div>
            <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <?php $__empty_1 = true; $__currentLoopData = $faculty; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $teacher): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php ($submission = $submissions->get($teacher->id)); ?>
                    <article class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <h2 class="font-black text-slate-900"><?php echo e($teacher->last_name); ?>, <?php echo e($teacher->first_name); ?></h2>
                                <?php if($submission): ?>
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black text-emerald-800">SUBMITTED</span>
                                <?php else: ?>
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-black text-amber-900">PENDING</span>
                                <?php endif; ?>
                            </div>
                            <p class="mt-1 text-xs font-semibold text-slate-500"><?php echo e($teacher->id_number ?: 'No employee ID'); ?> · <?php echo e($teacher->section ?: 'Faculty'); ?></p>
                            <?php if($submission): ?>
                                <p class="mt-3 text-sm font-black text-emerald-800">Average rating: <?php echo e(number_format((float) $submission->average_score, 2)); ?> / 5</p>
                            <?php endif; ?>
                        </div>
                        <?php if(!$submission): ?>
                            <a href="<?php echo e(route('management.evaluations.form', $teacher->id)); ?>" class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-[#590d0d] px-4 py-2.5 text-xs font-black text-white hover:bg-[#43090c]">
                                <i class="fa-solid fa-clipboard-check"></i> Evaluate Faculty
                            </a>
                        <?php else: ?>
                            <button disabled class="mt-5 rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-black text-slate-500"><i class="fa-solid fa-check mr-1"></i> Evaluation Complete</button>
                        <?php endif; ?>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm font-semibold text-slate-500 md:col-span-2 xl:col-span-3">No faculty accounts found.</div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views/management/evaluations/index.blade.php ENDPATH**/ ?>