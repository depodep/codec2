

<?php $__env->startSection('title', 'Attendance Records - SIATRACK'); ?>

<?php $__env->startSection('content'); ?>
<div class="w-full min-h-screen flex flex-col bg-slate-50/70">

    <!-- Top Header Bar -->
    <header class="bg-white border-b-2 border-slate-200 pl-8 lg:pl-12 pr-6 lg:pr-8 py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 sticky top-0 z-20 shadow-xs w-full">
        <div class="flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-[#8b1818] text-white flex items-center justify-center text-base shadow-xs shrink-0">
                <i class="fa-solid fa-clipboard-user text-amber-300"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Attendance Records</h1>
                    <span class="px-3 py-1 rounded-xl text-xs font-black bg-amber-100 text-amber-900 border border-amber-300 uppercase tracking-wide shadow-2xs">A.Y. <?php echo e($activeSchoolYear ?? '2027-2028'); ?></span>
                </div>
                <p class="text-xs text-slate-500 font-bold mt-0.5">Real-time gate logging, period tracking, and automated SMS alerts</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <?php if(\Illuminate\Support\Facades\Route::has('admin.attendance.export')): ?>
                <a href="<?php echo e(route('admin.attendance.export', request()->query())); ?>" 
                   class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded-2xl bg-[#8b1818] hover:bg-[#731414] text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-red-950/20 hover:shadow-xl transition-all duration-150 active:scale-[0.98] border-b-4 border-[#5e0f0f] shrink-0">
                    <i class="fa-solid fa-file-csv text-sm text-amber-300"></i>
                    <span>Export CSV</span>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="pt-10 pb-12 pl-8 lg:pl-12 pr-6 lg:pr-8 w-full space-y-8 flex-1">

        <!-- Flash Notifications -->
        <?php if(session('success')): ?>
            <div class="p-4 bg-emerald-50 border-2 border-emerald-300 text-emerald-900 text-xs font-bold rounded-2xl flex items-center gap-2.5 shadow-xs w-full">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                <span><?php echo e(session('success')); ?></span>
            </div>
        <?php endif; ?>

        <!-- ================= KPI Summary Row ================= -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
            <!-- 1. Present Today -->
            <div class="p-6 bg-white rounded-2xl border-2 border-slate-200 shadow-xs flex flex-col justify-between min-h-[155px] hover:shadow-md hover:border-emerald-300 transition w-full">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-black text-slate-500 uppercase tracking-wider">Present Today</p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <h3 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tight"><?php echo e(number_format($presentTodayCount ?? 0)); ?></h3>
                            <span class="text-xs font-black text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-md border border-emerald-200"><?php echo e($attendanceRate ?? '0%'); ?></span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 border-2 border-emerald-200 text-emerald-700 flex items-center justify-center text-xl shadow-xs shrink-0">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                </div>
                <div class="pt-3.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600 font-bold">
                    <span>Enrolled Total: <?php echo e($totalStudents ?? 0); ?></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                </div>
            </div>

            <!-- 2. Late Arrivals -->
            <div class="p-6 bg-white rounded-2xl border-2 border-slate-200 shadow-xs flex flex-col justify-between min-h-[155px] hover:shadow-md hover:border-amber-300 transition w-full">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-black text-slate-500 uppercase tracking-wider">Late Arrivals</p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <h3 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tight"><?php echo e(number_format($lateTodayCount ?? 0)); ?></h3>
                            <span class="text-xs font-black text-amber-800 bg-amber-50 px-2.5 py-0.5 rounded-md border border-amber-200">A.Y. <?php echo e($activeSchoolYear ?? '2027-2028'); ?></span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 border-2 border-amber-200 text-amber-700 flex items-center justify-center text-xl shadow-xs shrink-0">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                </div>
                <div class="pt-3.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600 font-bold">
                    <span>Past 8:00 AM Cutoff</span>
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                </div>
            </div>

            <!-- 3. Unrecorded / Absences -->
            <div class="p-6 bg-white rounded-2xl border-2 border-slate-200 shadow-xs flex flex-col justify-between min-h-[155px] hover:shadow-md hover:border-red-300 transition w-full">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-black text-slate-500 uppercase tracking-wider">Unrecorded / Absent</p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <h3 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tight"><?php echo e(number_format($absentTodayCount ?? 0)); ?></h3>
                            <span class="text-xs font-black text-[#8b1818] bg-red-50 px-2.5 py-0.5 rounded-md border border-red-200"><?php echo e($absentRate ?? '0%'); ?></span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-red-50 border-2 border-red-200 text-[#8b1818] flex items-center justify-center text-xl shadow-xs shrink-0">
                        <i class="fa-solid fa-user-xmark"></i>
                    </div>
                </div>
                <div class="pt-3.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600 font-bold">
                    <span>Pending Gate Scans</span>
                    <span class="w-2.5 h-2.5 rounded-full bg-[#8b1818]"></span>
                </div>
            </div>
        </div>

        <!-- ================= TOP CONTROLS: FILTERS & SEARCH ================= -->
        <form method="GET" action="<?php echo e(url()->current()); ?>" class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between w-full">
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Grade Filter (11, 12) -->
                <select name="grade_level" onchange="this.form.submit()" class="py-2.5 px-3.5 text-xs font-bold rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-700 focus:bg-white focus:outline-none focus:border-[#8b1818] transition shadow-2xs cursor-pointer">
                    <option value="">All Grade Levels</option>
                    <option value="11" <?php echo e((string)request('grade_level') === '11' ? 'selected' : ''); ?>>Grade 11</option>
                    <option value="12" <?php echo e((string)request('grade_level') === '12' ? 'selected' : ''); ?>>Grade 12</option>
                </select>

                <!-- Track Filter (1 = Academic Track, 2 = Technical-Professional) -->
                <select name="track" onchange="this.form.submit()" class="py-2.5 px-3.5 text-xs font-bold rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-700 focus:bg-white focus:outline-none focus:border-[#8b1818] transition shadow-2xs cursor-pointer">
                    <option value="">All Tracks</option>
                    <option value="1" <?php echo e((string)request('track') === '1' ? 'selected' : ''); ?>>Academic Track</option>
                    <option value="2" <?php echo e((string)request('track') === '2' ? 'selected' : ''); ?>>Technical-Professional</option>
                </select>

                <!-- Section Filter (1 = Amber, 2 = Crystal, 3 = Pearl, 4 = Turquoise) -->
                <select name="section" onchange="this.form.submit()" class="py-2.5 px-3.5 text-xs font-bold rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-700 focus:bg-white focus:outline-none focus:border-[#8b1818] transition shadow-2xs cursor-pointer">
                    <option value="">All Sections</option>
                    <option value="1" <?php echo e((string)request('section') === '1' ? 'selected' : ''); ?>>Amber</option>
                    <option value="2" <?php echo e((string)request('section') === '2' ? 'selected' : ''); ?>>Crystal</option>
                    <option value="3" <?php echo e((string)request('section') === '3' ? 'selected' : ''); ?>>Pearl</option>
                    <option value="4" <?php echo e((string)request('section') === '4' ? 'selected' : ''); ?>>Turquoise</option>
                </select>

                <!-- Status Filter -->
                <select name="status" onchange="this.form.submit()" class="py-2.5 px-3.5 text-xs font-bold rounded-xl border-2 border-slate-200 bg-slate-50 text-slate-700 focus:bg-white focus:outline-none focus:border-[#8b1818] transition shadow-2xs cursor-pointer">
                    <option value="">All Statuses</option>
                    <option value="ON-TIME" <?php echo e(request('status') == 'ON-TIME' ? 'selected' : ''); ?>>On-Time</option>
                    <option value="LATE" <?php echo e(request('status') == 'LATE' ? 'selected' : ''); ?>>Late</option>
                </select>

                <?php if(request()->hasAny(['grade_level', 'track', 'section', 'status', 'search'])): ?>
                    <a href="<?php echo e(url()->current()); ?>" class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl text-xs font-black transition shrink-0 shadow-2xs inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-left text-[11px]"></i>
                        <span>Clear</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Search Box -->
            <div class="relative w-full md:w-80">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Search student name or ID..." 
                       class="w-full pl-9 pr-4 py-2.5 text-xs font-bold border-2 border-slate-200 rounded-2xl focus:border-[#8b1818] outline-none bg-white transition shadow-2xs">
            </div>
        </form>

        <!-- ================= Two-Column Content Grid ================= -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 w-full">
            
            <!-- Attendance Records Table (8 Cols) -->
            <div class="lg:col-span-8 p-6 lg:p-8 bg-white rounded-3xl border-2 border-slate-200 shadow-xs space-y-6">
                <div class="flex items-center justify-between border-b-2 border-slate-100 pb-5">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-red-100 text-[#8b1818] flex items-center justify-center font-black text-lg shadow-2xs shrink-0">
                            <i class="fa-solid fa-clipboard-list"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900 tracking-tight">Gate Attendance Log</h2>
                            <p class="text-xs text-slate-500 font-bold mt-0.5">Live taps with academic track, grade, and section placement</p>
                        </div>
                    </div>
                    <div>
                        <span class="text-xs font-black text-[#8b1818] bg-red-50 px-3.5 py-1.5 rounded-full border border-red-200 shadow-2xs">
                            Total Scans: <?php echo e(method_exists($logs, 'total') ? $logs->total() : count($logs)); ?>

                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-2xl border-2 border-slate-200 shadow-2xs w-full">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/90 text-slate-700 uppercase font-black tracking-wider text-xs border-b-2 border-slate-200">
                                <th class="py-4 px-5">Student Details</th>
                                <th class="py-4 px-5 text-center">Academic Placement</th>
                                <th class="py-4 px-5 text-center">Time In</th>
                                <th class="py-4 px-5 text-center">Time Out</th>
                                <th class="py-4 px-5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm font-semibold text-slate-800">
                            <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $trackLabel = match((int)($log->track ?? $log->strand ?? 0)) {
                                        1 => 'Academic Track',
                                        2 => 'Technical-Professional',
                                        default => 'General Track'
                                    };
                                    $sectionLabel = match((int)($log->section ?? 0)) {
                                        1 => 'Amber',
                                        2 => 'Crystal',
                                        3 => 'Pearl',
                                        4 => 'Turquoise',
                                        default => !empty($log->section) ? 'Sec. ' . $log->section : 'Unassigned'
                                    };
                                    $gradeLabel = !empty($log->grade_level) ? 'Grade ' . $log->grade_level : 'Grade 11';
                                ?>
                                <tr class="hover:bg-red-50/40 transition">
                                    <!-- Student Name & ID -->
                                    <td class="py-4 px-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-red-100 border border-red-200 text-[#8b1818] text-xs font-black flex items-center justify-center shrink-0 shadow-2xs">
                                                <?php echo e(strtoupper(substr($log->first_name ?? 'S', 0, 1))); ?><?php echo e(strtoupper(substr($log->last_name ?? 'T', 0, 1))); ?>

                                            </div>
                                            <div>
                                                <span class="text-slate-900 font-extrabold block leading-tight"><?php echo e($log->first_name ?? ''); ?> <?php echo e($log->last_name ?? ''); ?></span>
                                                <span class="text-xs text-slate-400 font-mono font-bold"><?php echo e($log->id_number ?? 'N/A'); ?></span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Grade, Track & Section -->
                                    <td class="py-4 px-5 text-center">
                                        <div class="space-y-1">
                                            <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-800 text-xs font-black border border-slate-300 shadow-2xs inline-block">
                                                <?php echo e($gradeLabel); ?>

                                            </span>
                                            <div>
                                                <span class="px-2 py-0.5 rounded-md bg-red-50 text-[#8b1818] text-[10px] font-black border border-red-200">
                                                    <?php echo e($trackLabel); ?> • <?php echo e($sectionLabel); ?>

                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Time In -->
                                    <td class="py-4 px-5 text-center text-slate-900 font-mono font-bold text-xs">
                                        <?php if(isset($log->time_in)): ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-50 border border-slate-200 shadow-2xs">
                                                <i class="fa-regular fa-clock text-amber-600 text-xs"></i>
                                                <span><?php echo e(\Carbon\Carbon::parse($log->time_in)->format('h:i:s A')); ?></span>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-400 font-sans italic text-xs">--:--:--</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Time Out -->
                                    <td class="py-4 px-5 text-center text-slate-900 font-mono font-bold text-xs">
                                        <?php if(isset($log->time_out)): ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-50 border border-slate-200 shadow-2xs">
                                                <i class="fa-regular fa-clock text-blue-600 text-xs"></i>
                                                <span><?php echo e(\Carbon\Carbon::parse($log->time_out)->format('h:i:s A')); ?></span>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-400 font-sans italic text-xs">--:--:--</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="py-4 px-5 text-center">
                                        <?php if(strtoupper($log->status ?? '') === 'ON-TIME'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-2xs">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> ON-TIME
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-900 border border-amber-300 shadow-2xs">A.Y. <?php echo e($activeSchoolYear ?? '2027-2028'); ?></span> LATE
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="5" class="py-16 text-center text-slate-400 font-medium">
                                        <div class="w-14 h-14 rounded-2xl bg-slate-100 border-2 border-slate-200 text-slate-400 flex items-center justify-center mx-auto mb-3.5 text-2xl shadow-2xs">
                                            <i class="fa-solid fa-clipboard-question"></i>
                                        </div>
                                        <p class="text-base font-extrabold text-slate-800">No Attendance Records Found</p>
                                        <p class="text-xs text-slate-500 font-semibold mt-1">Try adjusting your filters or wait for live NFC card taps.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if(method_exists($logs, 'hasPages') && $logs->hasPages()): ?>
                    <div class="pt-2">
                        <?php echo e($logs->appends(request()->query())->links()); ?>

                    </div>
                <?php endif; ?>
            </div>

            <!-- SMS Alert Verification Card (4 Cols) -->
            <div class="lg:col-span-4 p-6 lg:p-8 bg-white rounded-3xl border-2 border-slate-200 shadow-xs space-y-6">
                <div class="flex items-center justify-between border-b-2 border-slate-100 pb-5">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-black text-lg shadow-2xs shrink-0">
                            <i class="fa-solid fa-comment-sms"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900 tracking-tight">SMS Alert Logs</h2>
                            <p class="text-xs text-slate-500 font-bold mt-0.5">Parent notification delivery status</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-2xl border-2 border-slate-200 shadow-2xs w-full">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/90 text-slate-700 uppercase font-black tracking-wider text-xs border-b-2 border-slate-200">
                                <th class="py-4 px-4">Recipient</th>
                                <th class="py-4 px-4">Contact</th>
                                <th class="py-4 px-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm font-semibold text-slate-800">
                            <?php $__empty_1 = true; $__currentLoopData = $smsLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sms): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr class="hover:bg-emerald-50/30 transition">
                                    <td class="py-4 px-4">
                                        <span class="font-extrabold text-slate-900 block leading-tight text-xs"><?php echo e($sms->recipient ?? 'Parent / Guardian'); ?></span>
                                    </td>
                                    <td class="py-4 px-4 font-mono font-bold text-xs text-slate-700">
                                        <?php echo e($sms->phone_number ?? 'N/A'); ?>

                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-2xs">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> <?php echo e($sms->status ?? 'Delivered'); ?>

                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="3" class="py-12 text-center text-slate-400 font-medium">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 border-2 border-slate-200 text-slate-400 flex items-center justify-center mx-auto mb-2 text-xl shadow-2xs">
                                            <i class="fa-solid fa-comment-slash"></i>
                                        </div>
                                        <p class="text-sm font-extrabold text-slate-800">No SMS Dispatches</p>
                                        <p class="text-[11px] text-slate-500 font-semibold mt-0.5">Automated SMS logs will register here.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </main>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views\admin\attendance.blade.php ENDPATH**/ ?>