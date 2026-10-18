<?php $__env->startSection('title', 'System Audit Logs - SIATRACK'); ?>

<?php $__env->startSection('content'); ?>
<div class="w-full min-h-screen flex flex-col bg-slate-50/70 p-6 lg:p-10 space-y-8">
    
    <!-- Top Header Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border-2 border-slate-200 shadow-xs">
        <div class="flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-red-50 text-[#590d0d] border border-red-200 flex items-center justify-center transition shrink-0 shadow-2xs">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight">System Audit Logs</h1>
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black bg-red-100 text-[#590d0d] border border-red-300 uppercase">Security & Accountability</span>
                </div>
                <p class="text-xs text-slate-500 font-bold mt-0.5">Monitor and track important activities and changes made within the system.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Date Range Form -->
            <form method="GET" action="<?php echo e(route('admin.audit-logs')); ?>" class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-2xl border-2 border-slate-200">
                <input type="date" name="start_date" value="<?php echo e(request('start_date', $startDate)); ?>" class="py-1.5 px-2 text-xs font-bold text-slate-800 bg-white border border-slate-200 rounded-xl focus:outline-none">
                <span class="text-xs font-black text-slate-400">to</span>
                <input type="date" name="end_date" value="<?php echo e(request('end_date', $endDate)); ?>" onchange="this.form.submit()" class="py-1.5 px-2 text-xs font-bold text-slate-800 bg-white border border-slate-200 rounded-xl focus:outline-none">
            </form>

            <!-- Export Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" type="button" class="px-4 py-2.5 bg-[#590d0d] hover:bg-[#430909] text-white text-xs font-black rounded-xl shadow-md transition cursor-pointer flex items-center gap-2">
                    <i class="fa-solid fa-download text-xs text-amber-300"></i>
                    <span>Export Logs</span>
                    <i class="fa-solid fa-chevron-down text-[10px]"></i>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-40 bg-white rounded-2xl border-2 border-slate-200 shadow-xl py-2 z-30">
                    <a href="<?php echo e(route('admin.audit-logs.export', array_merge(request()->all(), ['format' => 'csv']))); ?>" class="block px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Export as CSV</a>
                    <a href="<?php echo e(route('admin.audit-logs.export', array_merge(request()->all(), ['format' => 'excel']))); ?>" class="block px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Export as Excel</a>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== ASYMMETRIC STATISTIC SECTION ==================== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 w-full">
        
        <!-- LEFT: Featured Large Card for Total Activities (Clean White Theme with High Contrast) -->
        <div class="bg-white p-8 rounded-3xl border-2 border-slate-200 shadow-xs flex flex-col justify-between relative overflow-hidden group hover:border-[#590d0d] transition">
            <div class="absolute -right-4 -bottom-4 text-slate-100 text-9xl font-black pointer-events-none group-hover:text-red-50 transition">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div class="flex items-start justify-between relative z-10">
                <div>
                    <span class="text-[11px] font-black uppercase tracking-wider text-[#590d0d] bg-red-50 px-3 py-1 rounded-full border border-red-200">System Pulse</span>
                    <h2 class="text-sm font-black text-slate-900 tracking-tight mt-3">TOTAL ACTIVITIES</h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-red-50 border-2 border-red-200 text-[#590d0d] flex items-center justify-center text-xl shadow-xs">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
            </div>
            <div class="my-6 relative z-10">
                <h3 class="text-5xl lg:text-6xl font-black tracking-tight text-slate-900"><?php echo e(number_format($totalActivities)); ?></h3>
                <p class="text-xs text-slate-500 font-bold mt-2 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Activities recorded for selected date range
                </p>
            </div>
            <div class="pt-4 border-t border-slate-100 text-[11px] text-slate-600 font-bold relative z-10 flex items-center justify-between">
                <span>Read-Only Audit Trail</span>
                <span class="text-emerald-600">Secure & Reliable</span>
            </div>
        </div>

        <!-- RIGHT: Three Smaller Stacked Cards -->
        <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-6">
            
            <!-- 1. Login Activities -->
            <div class="bg-white p-6 rounded-3xl border-2 border-slate-200 shadow-xs flex flex-col justify-between hover:border-blue-300 transition">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Authentication</span>
                        <h4 class="text-sm font-black text-slate-900 mt-0.5">LOGIN ACTIVITIES</h4>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-200 text-blue-600 flex items-center justify-center text-base">
                        <i class="fa-solid fa-right-to-bracket"></i>
                    </div>
                </div>
                <div class="my-4">
                    <h3 class="text-3xl font-black text-slate-900 tracking-tight"><?php echo e(number_format($loginActivities)); ?></h3>
                    <p class="text-[11px] text-slate-400 font-bold mt-1">Login activities recorded</p>
                </div>
                <div class="pt-3 border-t border-slate-100 text-[11px] text-blue-600 font-bold flex items-center gap-1">
                    <i class="fa-solid fa-circle-info"></i> User access tracking
                </div>
            </div>

            <!-- 2. Data Changes -->
            <div class="bg-white p-6 rounded-3xl border-2 border-slate-200 shadow-xs flex flex-col justify-between hover:border-amber-300 transition">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Modifications</span>
                        <h4 class="text-sm font-black text-slate-900 mt-0.5">DATA CHANGES</h4>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center text-base">
                        <i class="fa-solid fa-database"></i>
                    </div>
                </div>
                <div class="my-4">
                    <h3 class="text-3xl font-black text-slate-900 tracking-tight"><?php echo e(number_format($dataChanges)); ?></h3>
                    <p class="text-[11px] text-slate-400 font-bold mt-1">Records modified today</p>
                </div>
                <div class="pt-3 border-t border-slate-100 text-[11px] text-amber-600 font-bold flex items-center gap-1">
                    <i class="fa-solid fa-pen-to-square"></i> Record updates & edits
                </div>
            </div>

            <!-- 3. Failed Activities -->
            <div class="bg-white p-6 rounded-3xl border-2 <?php echo e($failedActivities > 0 ? 'border-rose-300 bg-rose-50/30' : 'border-slate-200'); ?> shadow-xs flex flex-col justify-between hover:border-rose-400 transition">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[11px] font-black <?php echo e($failedActivities > 0 ? 'text-rose-600' : 'text-slate-400'); ?> uppercase tracking-wider">Security Alerts</span>
                        <h4 class="text-sm font-black text-slate-900 mt-0.5">FAILED ACTIVITIES</h4>
                    </div>
                    <div class="w-10 h-10 rounded-xl <?php echo e($failedActivities > 0 ? 'bg-rose-100 border border-rose-300 text-rose-600 animate-bounce' : 'bg-slate-100 text-slate-600'); ?> flex items-center justify-center text-base">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                </div>
                <div class="my-4">
                    <h3 class="text-3xl font-black <?php echo e($failedActivities > 0 ? 'text-rose-600' : 'text-slate-900'); ?> tracking-tight"><?php echo e(number_format($failedActivities)); ?></h3>
                    <p class="text-[11px] <?php echo e($failedActivities > 0 ? 'text-rose-700 font-black' : 'text-slate-400 font-bold'); ?> mt-1">Requires attention</p>
                </div>
                <div class="pt-3 border-t <?php echo e($failedActivities > 0 ? 'border-rose-200 text-rose-700' : 'border-slate-100 text-slate-500'); ?> text-[11px] font-bold flex items-center gap-1">
                    <i class="fa-solid fa-shield-dog"></i> Failed security triggers
                </div>
            </div>

        </div>
    </div>

    <!-- ==================== SYSTEM ACTIVITY TREND ==================== -->
    <div class="bg-white p-6 lg:p-8 rounded-3xl border-2 border-slate-200 shadow-xs space-y-6 w-full">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-900 tracking-tight">System Activity Trend</h3>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Hourly timeline distribution of recorded system operations</p>
            </div>
            <div class="w-9 h-9 rounded-xl bg-red-50 border border-red-200 text-[#590d0d] flex items-center justify-center text-xs shadow-2xs">
                <i class="fa-solid fa-chart-area"></i>
            </div>
        </div>
        <div class="relative h-72 w-full">
            <canvas id="systemTrendChart"></canvas>
        </div>
    </div>

    <!-- ==================== ACTIVITY FILTERS ==================== -->
    <div class="bg-white p-6 lg:p-8 rounded-3xl border-2 border-slate-200 shadow-xs w-full space-y-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-900 tracking-tight">Activity Filters</h3>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Filter audit logs by user, role, module, action, or status</p>
            </div>
            <div class="w-9 h-9 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center text-xs shadow-2xs">
                <i class="fa-solid fa-filter"></i>
            </div>
        </div>

        <form method="GET" action="<?php echo e(route('admin.audit-logs')); ?>" class="space-y-4">
            <input type="hidden" name="start_date" value="<?php echo e(request('start_date', $startDate)); ?>">
            <input type="hidden" name="end_date" value="<?php echo e(request('end_date', $endDate)); ?>">

            <!-- Search Bar -->
            <div class="relative w-full">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Search user, action, or activity description..." 
                       class="w-full pl-10 pr-4 py-3 text-xs font-bold border-2 border-slate-200 rounded-2xl focus:border-[#590d0d] outline-none bg-slate-50/50 transition">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- User Filter -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">User Name</label>
                    <input type="text" name="user" value="<?php echo e(request('user')); ?>" placeholder="Filter by user..." class="w-full py-2.5 px-3 text-xs font-bold text-slate-800 border-2 border-slate-200 rounded-xl bg-white focus:border-[#590d0d] outline-none">
                </div>

                <!-- Role Filter -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Role</label>
                    <select name="role" class="w-full py-2.5 px-3 text-xs font-bold text-slate-800 border-2 border-slate-200 rounded-xl bg-white focus:border-[#590d0d] outline-none cursor-pointer">
                        <option value="">All Roles</option>
                        <option value="Admin" <?php echo e(request('role') == 'Admin' ? 'selected' : ''); ?>>Admin</option>
                        <option value="Faculty" <?php echo e(request('role') == 'Faculty' ? 'selected' : ''); ?>>Faculty</option>
                        <option value="Student" <?php echo e(request('role') == 'Student' ? 'selected' : ''); ?>>Student</option>
                    </select>
                </div>

                <!-- Module Filter -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Module</label>
                    <select name="module" class="w-full py-2.5 px-3 text-xs font-bold text-slate-800 border-2 border-slate-200 rounded-xl bg-white focus:border-[#590d0d] outline-none cursor-pointer">
                        <option value="">All Modules</option>
                        <?php $__currentLoopData = ['Authentication', 'Students', 'Faculty', 'Attendance', 'Faculty Evaluation', 'SMS', 'Users', 'Settings']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mod): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($mod); ?>" <?php echo e(request('module') == $mod ? 'selected' : ''); ?>><?php echo e($mod); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Status</label>
                    <select name="status" class="w-full py-2.5 px-3 text-xs font-bold text-slate-800 border-2 border-slate-200 rounded-xl bg-white focus:border-[#590d0d] outline-none cursor-pointer">
                        <option value="">All Statuses</option>
                        <option value="Success" <?php echo e(request('status') == 'Success' ? 'selected' : ''); ?>>Success</option>
                        <option value="Failed" <?php echo e(request('status') == 'Failed' ? 'selected' : ''); ?>>Failed</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="<?php echo e(route('admin.audit-logs')); ?>" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-extrabold rounded-xl transition text-center">
                    Reset Filters
                </a>
                <button type="submit" class="px-6 py-2.5 bg-[#590d0d] hover:bg-[#430909] text-white text-xs font-black rounded-xl shadow-md transition cursor-pointer flex items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                    <span>Apply Filters</span>
                </button>
            </div>
        </form>
    </div>

    <!-- ==================== RECENT SYSTEM ACTIVITIES TABLE ==================== -->
    <div class="bg-white p-6 lg:p-8 rounded-3xl border-2 border-slate-200 shadow-xs space-y-6 w-full">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-900 tracking-tight">Recent System Activities</h3>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">Immutable audit trail of actions performed within SIATRACK</p>
            </div>
            <span class="px-3 py-1 bg-amber-50 text-amber-900 border border-amber-200 rounded-lg text-xs font-extrabold">
                Read-Only Logs
            </span>
        </div>

        <div class="overflow-x-auto rounded-2xl border-2 border-slate-200 shadow-2xs">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50/90 text-slate-700 uppercase font-black tracking-wider border-b-2 border-slate-200">
                    <tr>
                        <th class="py-4 px-5">Date & Time</th>
                        <th class="py-4 px-5">User</th>
                        <th class="py-4 px-5">Role</th>
                        <th class="py-4 px-5">Action</th>
                        <th class="py-4 px-5">Module</th>
                        <th class="py-4 px-5 text-center">Status</th>
                        <th class="py-4 px-5 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-800">
                    <?php $__empty_1 = true; $__currentLoopData = $auditLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="py-4 px-5 font-mono text-slate-600"><?php echo e($log->created_at->format('F d, Y, h:i A')); ?></td>
                        <td class="py-4 px-5 font-extrabold text-slate-900"><?php echo e($log->user_name); ?></td>
                        <td class="py-4 px-5">
                            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black <?php echo e(match($log->role) { 'Admin', 'Super Admin' => 'bg-red-100 text-[#590d0d]', 'Faculty' => 'bg-blue-100 text-blue-800', default => 'bg-slate-100 text-slate-800' }); ?>">
                                <?php echo e($log->role); ?>

                            </span>
                        </td>
                        <td class="py-4 px-5 font-bold text-slate-900"><?php echo e($log->action); ?></td>
                        <td class="py-4 px-5 text-slate-600"><?php echo e($log->module); ?></td>
                        <td class="py-4 px-5 text-center">
                            <?php if($log->status === 'Success'): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300">Success</span>
                            <?php else: ?>
                                <span class="px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-300">Failed</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-5 text-center">
                            <button type="button" 
                                    onclick="openDetailsModal('<?php echo e(addslashes($log->user_name)); ?>', '<?php echo e(addslashes($log->role)); ?>', '<?php echo e(addslashes($log->action)); ?>', '<?php echo e(addslashes($log->module)); ?>', '<?php echo e($log->created_at->format('F d, Y - h:i A')); ?>', '<?php echo e(addslashes($log->status)); ?>', '<?php echo e(addslashes($log->description ?? 'No additional description provided.')); ?>', '<?php echo e($log->ip_address ?? 'N/A'); ?>', '<?php echo e(addslashes($log->device ?? 'N/A')); ?>')"
                                    class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-[#590d0d] hover:text-white text-slate-700 text-xs font-bold transition cursor-pointer shadow-2xs">
                                View Details
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="py-16 text-center text-slate-400 font-bold">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 border-2 border-slate-200 text-slate-400 flex items-center justify-center mx-auto mb-3.5 text-2xl shadow-2xs">
                                <i class="fa-solid fa-folder-open"></i>
                            </div>
                            <p class="text-base font-extrabold text-slate-800">No Audit Logs Found</p>
                            <p class="text-xs text-slate-500 font-semibold mt-1">Try adjusting your date range or search filters.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($auditLogs->hasPages()): ?>
        <div class="pt-4">
            <?php echo e($auditLogs->withQueryString()->links()); ?>

        </div>
        <?php endif; ?>
    </div>

</div>

<!-- ==================== VIEW DETAILS MODAL ==================== -->
<div id="auditDetailsModal" class="fixed inset-0 z-50 bg-slate-950/60 hidden items-center justify-center p-4" style="backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);">
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl border-2 border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-6 py-5 bg-slate-50 border-b-2 border-slate-100 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-sm shadow-xs">
                    <i class="fa-solid fa-file-shield"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 tracking-tight">Activity Details</h3>
                    <p class="text-xs text-slate-500 font-semibold">Complete immutable system audit record</p>
                </div>
            </div>
            <button type="button" onclick="closeDetailsModal()" class="w-8 h-8 rounded-full bg-slate-200 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="p-6 overflow-y-auto space-y-4 text-xs font-semibold">
            <div class="grid grid-cols-2 gap-4 pb-4 border-b border-slate-100">
                <div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">User</span>
                    <span id="modalUser" class="text-sm font-extrabold text-slate-900">Admin 01</span>
                </div>
                <div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Role</span>
                    <span id="modalRole" class="text-sm font-bold text-[#590d0d]">Administrator</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 pb-4 border-b border-slate-100">
                <div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Action</span>
                    <span id="modalAction" class="text-sm font-bold text-slate-900">Updated Student Record</span>
                </div>
                <div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Module</span>
                    <span id="modalModule" class="text-sm font-bold text-slate-900">Students</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 pb-4 border-b border-slate-100">
                <div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Date & Time</span>
                    <span id="modalDateTime" class="text-slate-800 font-mono">September 18, 2026 - 8:32 AM</span>
                </div>
                <div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Status</span>
                    <span id="modalStatus" class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-800 mt-1">Success</span>
                </div>
            </div>

            <div>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1">Description</span>
                <p id="modalDescription" class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 leading-relaxed font-mono">
                    Updated the student's contact information.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-2">
                <div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">IP Address</span>
                    <span id="modalIp" class="font-mono text-slate-600">192.168.1.50</span>
                </div>
                <div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Device / OS</span>
                    <span id="modalDevice" class="font-mono text-slate-600">Windows</span>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end shrink-0">
            <button type="button" onclick="closeDetailsModal()" class="px-5 py-2.5 rounded-xl bg-[#590d0d] hover:bg-[#430909] text-white text-xs font-bold transition cursor-pointer">
                Close
            </button>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const trendLabels = <?php echo json_encode(array_keys($hourlyTrend)); ?>;
    const trendValues = <?php echo json_encode(array_values($hourlyTrend)); ?>;

    const ctx = document.getElementById('systemTrendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'System Activities',
                data: trendValues,
                borderColor: '#590d0d',
                backgroundColor: 'rgba(89, 13, 13, 0.08)',
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#f59e0b',
                pointBorderColor: '#590d0d',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0, font: { weight: 'bold' } }, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false }, ticks: { font: { weight: 'bold' } } }
            }
        }
    });
});

function openDetailsModal(user, role, action, module, dateTime, status, description, ip, device) {
    document.getElementById('modalUser').innerText = user;
    document.getElementById('modalRole').innerText = role;
    document.getElementById('modalAction').innerText = action;
    document.getElementById('modalModule').innerText = module;
    document.getElementById('modalDateTime').innerText = dateTime;
    document.getElementById('modalDescription').innerText = description;
    document.getElementById('modalIp').innerText = ip;
    document.getElementById('modalDevice').innerText = device;

    const statusEl = document.getElementById('modalStatus');
    statusEl.innerText = status;
    statusEl.className = status === 'Success' 
        ? 'inline-block px-2.5 py-0.5 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-800 mt-1' 
        : 'inline-block px-2.5 py-0.5 rounded-full text-[11px] font-black bg-rose-100 text-rose-800 mt-1';

    const modal = document.getElementById('auditDetailsModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeDetailsModal() {
    const modal = document.getElementById('auditDetailsModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views\admin\audit-logs\index.blade.php ENDPATH**/ ?>