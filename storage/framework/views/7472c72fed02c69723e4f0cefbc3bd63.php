<aside id="app-sidebar"
       x-data="{ 
           collapsed: localStorage.getItem('sidebar_collapsed') === 'true',
           toggle() {
               this.collapsed = !this.collapsed;
               localStorage.setItem('sidebar_collapsed', this.collapsed ? 'true' : 'false');
               if (this.collapsed) {
                   document.documentElement.classList.add('sidebar-collapsed');
               } else {
                   document.documentElement.classList.remove('sidebar-collapsed');
               }
               window.dispatchEvent(new CustomEvent('sidebar-toggle', { detail: this.collapsed }));
           }
       }" 
       @toggle-sidebar.window="toggle()"
       :class="collapsed ? 'w-20' : 'w-72'" 
       class="w-72 bg-[#590d0d] border-r border-red-950/40 flex flex-col justify-between shrink-0 h-screen fixed top-0 left-0 z-30 shadow-2xl transition-all duration-300">
    
    <!-- Top Accent Trim -->
    <div class="h-1.5 bg-gradient-to-r from-amber-400 via-amber-200 to-amber-400 w-full shrink-0"></div>

    <div class="flex-1 flex flex-col min-h-0">
        <!-- Brand Header & Hamburger Toggle -->
        <div class="p-4 border-b border-red-900/40 flex items-center justify-between bg-black/15 overflow-hidden">
            <div class="flex items-center gap-3 min-w-0" x-show="!collapsed" x-transition>
                <div class="w-10 h-10 rounded-xl bg-white p-1 border-2 border-amber-300 shadow-xs flex items-center justify-center shrink-0 overflow-hidden">
                    <img src="<?php echo e(asset('images/sia-logo.png')); ?>" alt="SIA Logo" class="w-full h-full object-contain">
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-base font-black tracking-tight text-white">SIATRACK</span>
                    </div>
                    <p class="text-[11px] font-bold text-red-200/70 truncate">Southern Isabela Academy</p>
                </div>
            </div>

            <!-- Minimized Logo View -->
            <div class="w-10 h-10 rounded-xl bg-white p-1 border-2 border-amber-300 flex items-center justify-center shrink-0 mx-auto" x-show="collapsed" x-cloak style="display: none;" x-transition>
                <img src="<?php echo e(asset('images/sia-logo.png')); ?>" alt="SIA Logo" class="w-full h-full object-contain">
            </div>

            <!-- Hamburger Toggle Button -->
            <button @click="toggle()" 
                    class="p-2 rounded-xl bg-red-950/60 hover:bg-red-950 text-amber-300 hover:text-white transition cursor-pointer shrink-0 border border-red-900/60 ml-2"
                    title="Toggle Sidebar">
                <i class="fa-solid fa-bars text-sm"></i>
            </button>
        </div>
        
        <!-- Navigation Links -->
        <nav class="p-3 space-y-5 overflow-y-auto flex-1 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:bg-red-950/50 [&::-webkit-scrollbar-thumb]:rounded-full">
            
            
            <?php if(auth()->user()->role_id == 1): ?>
                <div>
                    <p class="px-3 text-[10px] font-black uppercase tracking-wider text-red-200/60 mb-2" x-show="!collapsed">Management Menu</p>
                    <div class="space-y-1 text-sm font-bold">
                        <!-- Dashboard -->
                        <a href="<?php echo e(route('admin.dashboard')); ?>" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('admin.dashboard') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="Dashboard">
                            <i class="fa-solid fa-table-cells-large text-base <?php echo e(request()->routeIs('admin.dashboard') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                           <span class="text-[13px]" x-show="!collapsed">Dashboard</span>
                        </a>

                        <!-- User Management -->
                        <div class="space-y-1">
                            <a href="<?php echo e(route('admin.users.index')); ?>" 
                               class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-bold transition duration-150 <?php echo e(request()->routeIs('admin.users*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                               title="User Management">
                                <div class="flex items-center gap-3.5">
                                    <i class="fa-solid fa-users text-base <?php echo e(request()->routeIs('admin.users*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                                    <span class="text-[13px]" x-show="!collapsed">User Management</span>
                                </div>
                            </a>
                        </div>

                        <!-- System Audit Logs -->
                        <a href="<?php echo e(route('admin.audit-logs')); ?>"
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('admin.audit-logs*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="System Audit Logs">
                            <i class="fa-solid fa-shield-halved text-base <?php echo e(request()->routeIs('admin.audit-logs*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                           <span class="text-[13px]" x-show="!collapsed">System Audit Logs</span>
                        </a>
                    </div>
                </div>

                <div>
                    <p class="px-3 text-[10px] font-black uppercase tracking-wider text-red-200/60 mb-2" x-show="!collapsed">Modules & Setup</p>
                    <div class="space-y-1 text-sm font-bold">

                        <!-- Sections -->
                        <a href="<?php echo e(route('admin.sections')); ?>"
                            class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('admin.sections*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                            title="Sections">
                             <i class="fa-solid fa-layer-group text-base <?php echo e(request()->routeIs('admin.sections*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                             <span class="text-[13px]" x-show="!collapsed">Sections</span>
                        </a>

                        <!-- Schedules -->
                        <a href="<?php echo e(route('admin.schedules.index')); ?>"
                            class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('admin.schedules*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                            title="Schedules">
                             <i class="fa-solid fa-calendar-days text-base <?php echo e(request()->routeIs('admin.schedules*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                             <span class="text-[13px]" x-show="!collapsed">Schedules</span>
                        </a>
                        
                        <!-- Attendance Monitoring with Sub-items (Fixed highlighting scope) -->
                        <div x-data="{ open: <?php echo e(request()->routeIs('admin.attendance.live', 'admin.attendance.record') ? 'true' : 'false'); ?> }" class="space-y-1">
                            <button @click="open = !open" 
                                class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-bold transition duration-150 <?php echo e(request()->routeIs('admin.attendance.live', 'admin.attendance.record') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>">
                                <div class="flex items-center gap-3.5">
                                    <i class="fa-solid fa-id-card-clip text-base <?php echo e(request()->routeIs('admin.attendance.live', 'admin.attendance.record') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                                    <span class="text-[13px]" x-show="!collapsed">Attendance Monitoring</span>
                                </div>
                                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''" x-show="!collapsed"></i>
                            </button>
                            <div x-show="open && !collapsed" x-cloak class="pl-11 pr-2 space-y-1 py-1">
                                <a href="<?php echo e(route('admin.attendance.live')); ?>" class="block py-1.5 px-3 rounded-lg text-xs font-bold <?php echo e(request()->routeIs('admin.attendance.live*') ? 'bg-amber-400 text-amber-950 font-black shadow-xs' : 'text-red-200/80 hover:text-amber-300 hover:bg-red-900/40'); ?> transition">Live Tap Feed</a>
                                <a href="<?php echo e(route('admin.attendance.record')); ?>" class="block py-1.5 px-3 rounded-lg text-xs font-bold <?php echo e(request()->routeIs('admin.attendance.record*') ? 'bg-amber-400 text-amber-950 font-black shadow-xs' : 'text-red-200/80 hover:text-amber-300 hover:bg-red-900/40'); ?> transition">Attendance Record</a>
                            </div>
                        </div>

                        <!-- Absence and Telegram Notifications -->
                        <a href="<?php echo e(route('admin.message')); ?>"
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('admin.message*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="Absence Notifications">
                            <i class="fa-solid fa-bell text-base <?php echo e(request()->routeIs('admin.message*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                            <span class="text-[13px]" x-show="!collapsed">Absence Notifications</span>
                        </a>

                        <a href="<?php echo e(route('admin.telegram.index')); ?>"
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('admin.telegram*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="Telegram Parent Notifications">
                            <i class="fa-brands fa-telegram text-base <?php echo e(request()->routeIs('admin.telegram*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                            <span class="text-[13px]" x-show="!collapsed">Telegram Parents</span>
                        </a>

                        <!-- NFC Management -->
                        <a href="<?php echo e(route('admin.nfc.binding')); ?>"
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('admin.nfc*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="NFC Management">
                            <i class="fa-solid fa-id-card text-base <?php echo e(request()->routeIs('admin.nfc*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                            <span class="text-[13px]" x-show="!collapsed">NFC Management</span>
                        </a>

                        <!-- Faculty Evaluation with Sub-items (Fixed highlighting scope) -->
                        <div x-data="{ open: <?php echo e(request()->routeIs('admin.evaluations*') ? 'true' : 'false'); ?> }" class="space-y-1">
                            <button @click="open = !open" 
                                class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-bold transition duration-150 <?php echo e(request()->routeIs('admin.evaluations*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>">
                                <div class="flex items-center gap-3.5">
                                    <i class="fa-solid fa-star-half-stroke text-base <?php echo e(request()->routeIs('admin.evaluations*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                                    <span class="text-[13px]" x-show="!collapsed">Faculty Evaluation</span>
                                </div>
                                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''" x-show="!collapsed"></i>
                            </button>
                            <div x-show="open && !collapsed" x-cloak class="pl-11 pr-2 space-y-1 py-1">
                                <a href="<?php echo e(route('admin.evaluations.periods')); ?>" class="block py-1.5 px-3 rounded-lg text-xs font-bold <?php echo e(request()->routeIs('admin.evaluations.periods*', 'admin.evaluations.forms.edit*') ? 'bg-amber-400 text-amber-950 font-black shadow-xs' : 'text-red-200/80 hover:text-amber-300 hover:bg-red-900/40'); ?> transition">Periods & Questions Setup</a>
                                <a href="<?php echo e(route('admin.evaluations.history')); ?>" class="block py-1.5 px-3 rounded-lg text-xs font-bold <?php echo e(request()->routeIs('admin.evaluations.history*') ? 'bg-amber-400 text-amber-950 font-black shadow-xs' : 'text-red-200/80 hover:text-amber-300 hover:bg-red-900/40'); ?> transition">Evaluation History</a>
                                <a href="<?php echo e(route('admin.evaluations.results')); ?>" class="block py-1.5 px-3 rounded-lg text-xs font-bold <?php echo e(request()->routeIs('admin.evaluations.results*') ? 'bg-amber-400 text-amber-950 font-black shadow-xs' : 'text-red-200/80 hover:text-amber-300 hover:bg-red-900/40'); ?> transition">Results & Ratings Overview</a>
                            </div>
                        </div>


                        <!-- Reports with Sub-items -->
                        <div x-data="{ open: <?php echo e(request()->routeIs('admin.reports*') ? 'true' : 'false'); ?> }" class="space-y-1">
                            <button @click="open = !open" 
                                    class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-bold transition duration-150 <?php echo e(request()->routeIs('admin.reports*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>">
                                <div class="flex items-center gap-3.5">
                                    <i class="fa-solid fa-file-invoice text-base <?php echo e(request()->routeIs('admin.reports*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                                    <span class="text-[13px]" x-show="!collapsed">Reports</span>
                                </div>
                                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''" x-show="!collapsed"></i>
                            </button>
                            <div x-show="open && !collapsed" x-cloak class="pl-11 pr-2 space-y-1 py-1">
                                <a href="<?php echo e(route('admin.reports.attendance')); ?>" class="block py-1.5 px-3 rounded-lg text-xs font-bold <?php echo e(request()->routeIs('admin.reports.attendance*') ? 'bg-amber-400 text-amber-950 font-black shadow-xs' : 'text-red-200/80 hover:text-amber-300 hover:bg-red-900/40'); ?> transition">Attendance Logs</a>
                                <a href="<?php echo e(route('admin.reports.sf2')); ?>" class="block py-1.5 px-3 rounded-lg text-xs font-bold <?php echo e(request()->routeIs('admin.reports.sf2*') ? 'bg-amber-400 text-amber-950 font-black shadow-xs' : 'text-red-200/80 hover:text-amber-300 hover:bg-red-900/40'); ?> transition">SF2 (DepEd Compliant)</a>
                                <a href="<?php echo e(route('admin.reports.evaluation')); ?>" class="block py-1.5 px-3 rounded-lg text-xs font-bold <?php echo e(request()->routeIs('admin.reports.evaluation*') ? 'bg-amber-400 text-amber-950 font-black shadow-xs' : 'text-red-200/80 hover:text-amber-300 hover:bg-red-900/40'); ?> transition">Faculty Evaluation Summary</a>
                                <a href="<?php echo e(route('admin.reports.users')); ?>" class="block py-1.5 px-3 rounded-lg text-xs font-bold <?php echo e(request()->routeIs('admin.reports.users*') ? 'bg-amber-400 text-amber-950 font-black shadow-xs' : 'text-red-200/80 hover:text-amber-300 hover:bg-red-900/40'); ?> transition">User Master List</a>
                            </div>
                        </div>

                        <!-- Settings (Direct Single Link - No Dropdown) -->
                        <a href="<?php echo e(route('admin.settings')); ?>" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('admin.settings*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="Settings">
                            <i class="fa-solid fa-gear text-base <?php echo e(request()->routeIs('admin.settings*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                            <span class="text-[13px]" x-show="!collapsed">Settings</span>
                        </a>

                    </div>
                </div>

            <?php elseif(auth()->user()->role_id == 4): ?>
                <div>
                    <p class="px-3 text-[10px] font-black uppercase tracking-wider text-red-200/60 mb-2" x-show="!collapsed">Management Portal</p>
                    <div class="space-y-1 text-sm font-bold">
                        <a href="<?php echo e(route('management.dashboard')); ?>"
                           class="group flex items-center gap-3.5 rounded-xl px-3.5 py-2.5 transition <?php echo e(request()->routeIs('management.dashboard') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="Management Dashboard">
                            <i class="fa-solid fa-chart-pie text-base <?php echo e(request()->routeIs('management.dashboard') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                            <span class="text-[13px]" x-show="!collapsed">Dashboard & Master Lists</span>
                        </a>
                        <?php if(auth()->user()->can_evaluate_faculty): ?>
                            <a href="<?php echo e(route('management.evaluations.index')); ?>"
                               class="group flex items-center gap-3.5 rounded-xl px-3.5 py-2.5 transition <?php echo e(request()->routeIs('management.evaluations*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                               title="Principal Faculty Evaluation">
                                <i class="fa-solid fa-star-half-stroke text-base <?php echo e(request()->routeIs('management.evaluations*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                                <span class="text-[13px]" x-show="!collapsed">Faculty Evaluation</span>
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo e(route('profile.edit')); ?>"
                           class="group flex items-center gap-3.5 rounded-xl px-3.5 py-2.5 text-white transition hover:bg-red-900/50 hover:text-amber-200"
                           title="Manage Account">
                            <i class="fa-solid fa-user-gear text-base text-amber-300 group-hover:text-white"></i>
                            <span class="text-[13px]" x-show="!collapsed">Manage Account</span>
                        </a>
                    </div>
                </div>

           
            <?php elseif(auth()->user()->role_id === 2): ?>
                <div>
                    <p class="px-3 text-[10px] font-black uppercase tracking-wider text-red-200/60 mb-2" x-show="!collapsed">Faculty Portal</p>
                    <div class="space-y-1 text-sm font-bold">
                        
                        <!-- Dashboard -->
                        <a href="<?php echo e(route('teacher.dashboard')); ?>" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('teacher.dashboard*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="Dashboard">
                            <i class="fa-solid fa-gauge text-base <?php echo e(request()->routeIs('teacher.dashboard*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                            <span class="text-[13px]" x-show="!collapsed">Dashboard</span>
                        </a>

                        <!-- Classes (Combined Schedule & Sections) -->
                        <a href="<?php echo e(route('teacher.classes')); ?>" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('teacher.classes*', 'teacher.schedules*', 'teacher.school-years*', 'teacher.schedule.students*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="Classes">
                            <i class="fa-solid fa-chalkboard-user text-base <?php echo e(request()->routeIs('teacher.classes*', 'teacher.schedules*', 'teacher.school-years*', 'teacher.schedule.students*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                            <span class="text-[13px]" x-show="!collapsed">Classes</span>
                        </a>

                        <!-- Student -->
                        <a href="<?php echo e(route('teacher.students')); ?>" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('teacher.students*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="Student">
                            <i class="fa-solid fa-user-graduate text-base <?php echo e(request()->routeIs('teacher.students*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                            <span class="text-[13px]" x-show="!collapsed">Students</span>
                        </a>

                        <!-- Absence and Telegram Notifications -->
                        <a href="<?php echo e(route('teacher.message')); ?>" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('teacher.message*') || request()->routeIs('teacher.messages*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="Absence Notifications">
                            <i class="fa-solid fa-bell text-base <?php echo e(request()->routeIs('teacher.message*') || request()->routeIs('teacher.messages*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                            <span class="text-[13px]" x-show="!collapsed">Absence Notifications</span>
                        </a>

                        <!-- Attendance Report -->
                        <a href="<?php echo e(route('teacher.reports')); ?>" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('teacher.reports*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="Attendance Report">
                            <i class="fa-solid fa-chart-pie text-base <?php echo e(request()->routeIs('teacher.reports*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                            <span class="text-[13px]" x-show="!collapsed">Attendance Report</span>
                        </a>

                        <!-- Faculty Evaluation -->
                        <a href="<?php echo e(route('teacher.evaluations.index')); ?>" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('teacher.evaluations.index', 'teacher.evaluations.peer*', 'teacher.evaluations.self*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="Faculty Evaluation">
                            <i class="fa-solid fa-star-half-stroke text-base <?php echo e(request()->routeIs('teacher.evaluations.index', 'teacher.evaluations.peer*', 'teacher.evaluations.self*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                            <span class="text-[13px]" x-show="!collapsed">Faculty Evaluation</span>
                        </a>

                        <!-- Manage Account -->
                        <a href="<?php echo e(route('profile.edit')); ?>" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('profile.edit') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="Manage Account">
                            <i class="fa-solid fa-user-gear text-base <?php echo e(request()->routeIs('profile.edit') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                            <span class="text-[13px]" x-show="!collapsed">Manage Account</span>
                        </a>
                    </div>
                </div>
            
            
            <?php elseif(auth()->user()->role_id === 3): ?>
                <?php
                    $sidebarEvalCycle = \Illuminate\Support\Facades\Schema::hasTable('evaluation_cycles')
                        ? \Illuminate\Support\Facades\DB::table('evaluation_cycles')->where('status', 'active')->where('is_active', 1)->first()
                        : null;
                    $sidebarEvalOpen = (bool) $sidebarEvalCycle || \Illuminate\Support\Facades\Cache::get('evaluations_open', false);
                ?>
                <div>
                    <p class="px-3 text-[10px] font-black uppercase tracking-wider text-red-200/60 mb-2" x-show="!collapsed">Student Portal</p>
                    <div class="space-y-1 text-sm font-bold">
                        <!-- My Dashboard -->
                        <a href="<?php echo e(route('student.dashboard')); ?>" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('student.dashboard*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="My Dashboard">
                            <i class="fa-solid fa-gauge text-base <?php echo e(request()->routeIs('student.dashboard*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                            <span class="text-[13px]" x-show="!collapsed">My Dashboard</span>
                        </a>

                        <!-- Class Schedules & Timetable -->
                        <a href="<?php echo e(route('student.schedules')); ?>" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('student.schedules*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="Class Schedules">
                            <i class="fa-solid fa-calendar-days text-base <?php echo e(request()->routeIs('student.schedules*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                            <span class="text-[13px]" x-show="!collapsed">Class Schedules</span>
                        </a>

                        <!-- Attendance Records -->
                        <a href="<?php echo e(route('student.attendance')); ?>" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('student.attendance*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="Attendance Records">
                            <i class="fa-solid fa-calendar-check text-base <?php echo e(request()->routeIs('student.attendance*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                            <span class="text-[13px]" x-show="!collapsed">Attendance Records</span>
                        </a>

                        <!-- Faculty Evaluation -->
                        <?php if($sidebarEvalOpen): ?>
                            <a href="<?php echo e(route('student.evaluations.index')); ?>" 
                               class="group flex items-center justify-between px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('student.evaluations*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                               title="Faculty Evaluation">
                                <div class="flex items-center gap-3.5">
                                    <i class="fa-solid fa-star-half-stroke text-base <?php echo e(request()->routeIs('student.evaluations*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                                    <span class="text-[13px]" x-show="!collapsed">Faculty Evaluation</span>
                                </div>
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse" x-show="!collapsed" title="Evaluation Active"></span>
                            </a>
                        <?php else: ?>
                            <div class="group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-white/40 cursor-not-allowed select-none"
                                 title="Faculty evaluation is currently closed">
                                <div class="flex items-center gap-3.5">
                                    <i class="fa-solid fa-lock text-base text-red-300/40"></i>
                                    <span class="text-[13px]" x-show="!collapsed">Faculty Evaluation</span>
                                </div>
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-black/20 text-red-200/50" x-show="!collapsed">
                                    Closed
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Manage Account -->
                        <a href="<?php echo e(route('student.profile.edit')); ?>" 
                           class="group flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl transition duration-150 <?php echo e(request()->routeIs('student.profile*') ? 'bg-amber-400 text-amber-950 shadow-md' : 'text-white hover:bg-red-900/50 hover:text-amber-200'); ?>"
                           title="Manage Account">
                            <i class="fa-solid fa-user-gear text-base <?php echo e(request()->routeIs('student.profile*') ? 'text-amber-950' : 'text-amber-300 group-hover:text-white'); ?>"></i>
                            <span class="text-[13px]" x-show="!collapsed">Manage Account</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

        </nav>
    </div>

    <!-- Bottom Logout -->
    <div class="p-3 border-t border-red-900/40 bg-black/15">
        <form method="POST" action="<?php echo e(route('logout')); ?>">
            <?php echo csrf_field(); ?>
            <button type="submit" 
                    class="w-full flex items-center justify-center gap-3 py-2.5 px-3 rounded-xl text-xs font-black text-white hover:text-amber-950 hover:bg-amber-400 bg-red-950/50 border border-red-900/50 transition cursor-pointer shadow-xs"
                    title="Sign Out">
                <i class="fa-solid fa-right-from-bracket text-sm text-amber-300 group-hover:text-amber-950"></i>
                <span x-show="!collapsed">Sign Out</span>
            </button>
       </form>
    </div>

</aside><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views/layouts/sidebar.blade.php ENDPATH**/ ?>