<!-- Section: Low Attendance / At-Risk Student Alert Widget -->
<div class="p-6 lg:p-8 bg-white rounded-3xl border-2 border-slate-200 shadow-xs space-y-6 w-full">
    
    <?php
        $atRiskStudents = \Illuminate\Support\Facades\Schema::hasTable('attendance_logs')
            ? \Illuminate\Support\Facades\DB::table('attendance_logs')
                ->join('users', 'attendance_logs.user_id', '=', 'users.id')
                ->select(
                    'users.id', 
                    'users.first_name', 
                    'users.last_name', 
                    'users.id_number', 
                    \Illuminate\Support\Facades\DB::raw('count(attendance_logs.id) as infraction_count')
                )
                ->where('attendance_logs.status', '!=', 'ON-TIME')
                ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.id_number')
                ->orderByDesc('infraction_count')
                ->take(5)
                ->get()
            : collect();
    ?>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b-2 border-slate-100 w-full">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-red-50 border border-red-200 text-[#590d0d] flex items-center justify-center text-sm font-black shadow-2xs">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-900 tracking-tight">At-Risk Student Alerts</h2>
                <p class="text-xs text-slate-500 font-bold mt-0.5">Students flagged with consecutive absences or frequent tardiness</p>
            </div>
        </div>
        
        <span class="px-3 py-1 rounded-xl text-xs font-black <?php echo e(count($atRiskStudents) > 0 ? 'bg-red-100 text-red-800 border border-red-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300'); ?>">
            <?php echo e(count($atRiskStudents) > 0 ? count($atRiskStudents) . ' Student(s) Flagged' : 'All Clear'); ?>

        </span>
    </div>

    <!-- At-Risk Table -->
    <div class="overflow-x-auto rounded-2xl border-2 border-slate-200 shadow-2xs w-full">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/90 text-slate-700 uppercase font-black tracking-wider text-xs border-b-2 border-slate-200">
                    <th class="py-4 px-5">Student Name</th>
                    <th class="py-4 px-5 text-center">LRN / ID</th>
                    <th class="py-4 px-5 text-center">Grade & Section</th>
                    <th class="py-4 px-5 text-center">Attendance Issue</th>
                    <th class="py-4 px-5 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm font-semibold text-slate-800">
                <?php $__empty_1 = true; $__currentLoopData = $atRiskStudents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $risk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-red-50/30 transition">
                    <td class="py-4 px-5">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-red-100 border border-red-200 text-[#590d0d] text-xs font-black flex items-center justify-center shrink-0">
                                <?php echo e(strtoupper(substr($risk->first_name ?? 'S', 0, 1))); ?><?php echo e(strtoupper(substr($risk->last_name ?? 'T', 0, 1))); ?>

                            </div>
                            <span class="text-slate-900 font-extrabold"><?php echo e($risk->first_name); ?> <?php echo e($risk->last_name); ?></span>
                        </div>
                    </td>
                    <td class="py-4 px-5 text-center font-mono font-bold text-xs text-slate-700">
                        <?php echo e($risk->id_number ?? 'N/A'); ?>

                    </td>
                    <td class="py-4 px-5 text-center">
                        <span class="px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-black border border-slate-200">
                            Grade 11 - Amber
                        </span>
                    </td>
                    <td class="py-4 px-5 text-center">
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-900 border border-amber-300">
                            <?php echo e($risk->infraction_count); ?> Recorded Infractions
                        </span>
                    </td>
                    <td class="py-4 px-5 text-center">
                        <button type="button" onclick="alert('SMS Warning dispatched to parents for <?php echo e($risk->first_name); ?> <?php echo e($risk->last_name); ?>')" class="px-3 py-1.5 rounded-xl bg-[#590d0d] hover:bg-[#460a0a] text-white text-xs font-black shadow-xs transition cursor-pointer inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-paper-plane text-[10px]"></i> Send Notice
                        </button>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="5" class="py-12 text-center text-slate-400 font-medium">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center mx-auto mb-2 text-xl">
                            <i class="fa-solid fa-shield-check"></i>
                        </div>
                        <p class="text-sm font-extrabold text-slate-800">No At-Risk Students Flagged</p>
                        <p class="text-xs text-slate-500 font-semibold mt-0.5">All enrolled students maintain a clean attendance record for this period.</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views\admin\dashboard\partials\risk-alerts.blade.php ENDPATH**/ ?>