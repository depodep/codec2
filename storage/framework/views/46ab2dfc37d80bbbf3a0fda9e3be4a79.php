

<?php $__env->startSection('title', 'Evaluate Faculty | SIATRACK'); ?>

<?php $__env->startSection('content'); ?>
<div class="min-h-screen bg-slate-50/70">
    <header class="flex flex-col justify-between gap-4 border-b-2 border-slate-200 bg-white px-6 py-5 sm:flex-row sm:items-center lg:px-10">
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-[#8b1818]">Principal Evaluation · <?php echo e($activeCycle->school_year); ?></p>
            <h1 class="mt-1 text-xl font-black text-slate-900"><?php echo e($teacher->last_name); ?>, <?php echo e($teacher->first_name); ?></h1>
            <p class="mt-1 text-xs font-semibold text-slate-500"><?php echo e($form->title ?? "Principal's Evaluation"); ?></p>
        </div>
        <a href="<?php echo e(route('management.evaluations.index')); ?>" class="rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-200"><i class="fa-solid fa-arrow-left mr-1"></i> Faculty List</a>
    </header>
    <main class="mx-auto max-w-4xl space-y-5 px-4 py-6 sm:px-6">
        <?php if($errors->any()): ?>
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-800"><?php echo e($errors->first()); ?></div>
        <?php endif; ?>
        <?php if(!empty($form?->instructions)): ?>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs font-semibold leading-relaxed text-amber-950"><?php echo e($form->instructions); ?></div>
        <?php endif; ?>
        <form action="<?php echo e(route('management.evaluations.store', $teacher->id)); ?>" method="POST" class="space-y-5">
            <?php echo csrf_field(); ?>
            <?php $__currentLoopData = $questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $question): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                    <?php if($question->category): ?>
                        <p class="mb-2 text-[10px] font-black uppercase tracking-wide text-[#8b1818]"><?php echo e($question->category); ?></p>
                    <?php endif; ?>
                    <p class="text-sm font-bold text-slate-900"><?php echo e($loop->iteration); ?>. <?php echo e($question->question); ?></p>
                    <div class="mt-4 grid grid-cols-5 gap-2">
                        <?php for($rating = 1; $rating <= 5; $rating++): ?>
                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-2 py-3 text-xs font-black text-slate-700 hover:border-[#8b1818] hover:bg-red-50">
                                <input type="radio" name="answers[<?php echo e($question->id); ?>]" value="<?php echo e($rating); ?>" <?php if(old('answers.'.$question->id) == $rating): echo 'checked'; endif; ?> <?php if((bool) $question->is_required): echo 'required'; endif; ?> class="accent-[#8b1818]">
                                <span><?php echo e($rating); ?></span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </section>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <label for="comments" class="text-xs font-black uppercase tracking-wide text-slate-700">Comments / Feedback (Optional)</label>
                <textarea id="comments" name="comments" rows="4" maxlength="2000" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm focus:border-[#8b1818] focus:outline-none"><?php echo e(old('comments')); ?></textarea>
            </section>
            <div class="flex justify-end">
                <button type="submit" class="rounded-xl bg-[#590d0d] px-6 py-3 text-xs font-black text-white shadow-xs hover:bg-[#43090c]"><i class="fa-solid fa-paper-plane mr-1"></i> Submit Principal Evaluation</button>
            </div>
        </form>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views/management/evaluations/form.blade.php ENDPATH**/ ?>