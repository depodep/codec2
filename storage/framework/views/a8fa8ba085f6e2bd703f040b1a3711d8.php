<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-black text-slate-900">Evaluate Faculty: Prof. <?php echo e($teacher->first_name); ?> <?php echo e($teacher->last_name); ?></h1>
            <p class="text-xs text-slate-500 font-semibold mt-0.5">Please rate each criteria objectively from 1 (Lowest) to 5 (Highest).</p>
        </div>
        <a href="<?php echo e(route('student.evaluations.index')); ?>" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black rounded-2xl transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Back
        </a>
    </div>

    <form action="<?php echo e(route('student.evaluations.store')); ?>" method="POST" class="space-y-6">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="evaluatee_id" value="<?php echo e($teacher->id); ?>">

        <?php $__currentLoopData = $groupedQuestions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $questions): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="bg-white rounded-3xl border-2 border-slate-200 p-6 shadow-xs">
                <h3 class="text-xs font-black text-[#8b1818] uppercase tracking-wider mb-4 pb-2 border-b border-slate-100"><?php echo e($category); ?></h3>
                <div class="space-y-6">
                    <?php $__currentLoopData = $questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="space-y-2">
                            <p class="text-xs font-bold text-slate-800"><?php echo e($loop->iteration); ?>. <?php echo e($q->question); ?></p>
                            
                            <?php
                                $qType = $q->type ?? 'likert';
                                $options = [];
                                if (!empty($q->options)) {
                                    $options = is_array($q->options) ? $q->options : json_decode($q->options, true);
                                    if (is_string($options)) {
                                        $decodedOptions = json_decode($options, true);
                                        $options = is_array($decodedOptions) ? $decodedOptions : $options;
                                    }
                                    if (!is_array($options) && is_string($q->options)) {
                                        $options = array_map('trim', explode(',', $options));
                                    }
                                }
                            ?>

                            <?php if($qType === 'yes_no'): ?>
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700 bg-slate-50 px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 transition flex-1 justify-center">
                                        <input type="radio" name="scores[<?php echo e($q->id); ?>]" value="5" <?php echo e(($q->is_required ?? true) ? 'required' : ''); ?> class="accent-[#8b1818]">
                                        <span>Yes</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700 bg-slate-50 px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 transition flex-1 justify-center">
                                        <input type="radio" name="scores[<?php echo e($q->id); ?>]" value="1" <?php echo e(($q->is_required ?? true) ? 'required' : ''); ?> class="accent-[#8b1818]">
                                        <span>No</span>
                                    </label>
                                </div>
                            <?php elseif($qType === 'multiple_choice'): ?>
                                <div class="space-y-2">
                                    <?php $__currentLoopData = ($options ?: ['Option 1', 'Option 2']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optIdx => $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <label class="flex items-center gap-3 cursor-pointer text-xs font-semibold text-slate-700 bg-slate-50 p-3 rounded-xl border border-slate-200 hover:bg-slate-100 transition">
                                            <input type="radio" name="scores[<?php echo e($q->id); ?>]" value="<?php echo e($optIdx + 1); ?>" <?php echo e(($q->is_required ?? true) ? 'required' : ''); ?> class="accent-[#8b1818]">
                                            <span><?php echo e($opt); ?></span>
                                        </label>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php elseif($qType === 'open_ended'): ?>
                                <textarea name="text_responses[<?php echo e($q->id); ?>]" rows="2" placeholder="Write your response..." class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:border-[#8b1818]"></textarea>
                            <?php else: ?>
                                <div class="flex items-center gap-2 sm:gap-4">
                                    <?php for($score = 1; $score <= 5; $score++): ?>
                                        <label class="flex items-center gap-1.5 cursor-pointer text-xs font-bold text-slate-600 bg-slate-50 px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-100 transition flex-1 justify-center">
                                            <input type="radio" name="scores[<?php echo e($q->id); ?>]" value="<?php echo e($score); ?>" <?php echo e(($q->is_required ?? true) ? 'required' : ''); ?> class="accent-[#8b1818]">
                                            <span><?php echo e($score); ?></span>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <div class="bg-white rounded-3xl border-2 border-slate-200 p-6 shadow-xs">
            <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Additional Comments / Feedback (Optional)</label>
            <textarea name="comments" rows="4" placeholder="Write any constructive feedback or comments for this instructor..." class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-semibold text-slate-800 focus:outline-none focus:border-[#8b1818]"></textarea>
        </div>

        <div class="flex justify-end gap-3">
            <button type="submit" class="px-6 py-3 bg-[#8b1818] hover:bg-opacity-90 text-white text-xs font-black rounded-2xl shadow-md transition">
                <i class="fa-solid fa-paper-plane mr-1.5"></i> Submit Evaluation
            </button>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views\student\evaluations\take.blade.php ENDPATH**/ ?>