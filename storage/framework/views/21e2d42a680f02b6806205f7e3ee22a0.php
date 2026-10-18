<!-- ================= Modal: Edit Admin Profile ================= -->
<?php
    $currentAdminPassword = old('current_password', 
        auth()->user()->password 
        ?? auth()->user()->getAuthPassword() 
        ?? (\Illuminate\Support\Facades\DB::table('users')->where('id', auth()->id())->value('password') ?? '')
    );
?>

<div id="editProfileModal" 
     class="fixed inset-0 z-50 bg-slate-950/65 hidden items-center justify-center p-4 backdrop-blur-md transition-all duration-300"
     onclick="if(event.target === this) closeEditProfileModal()">
    
    <form action="<?php echo e(route('admin.profile.update')); ?>" method="POST" id="adminProfileForm" 
          class="bg-white w-full max-w-xl rounded-3xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        <?php echo csrf_field(); ?>
        
        <!-- Pinned Header -->
        <div class="px-7 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#590d0d] text-amber-300 flex items-center justify-center text-xs font-black tracking-wider uppercase shadow-xs shrink-0">
                    ADM
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 tracking-tight">Administrator Profile</h3>
                    <p class="text-xs text-slate-500 font-medium">Manage administrative credentials and personal details</p>
                </div>
            </div>
            <button type="button" onclick="closeEditProfileModal()" 
                    class="w-8 h-8 rounded-lg bg-slate-200/60 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center text-base font-bold transition cursor-pointer">
                &times;
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-4 text-slate-800 text-xs">
            
            <!-- SECTION 1: PROFILE INFORMATION -->
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-500">Personal Information</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">First Name</label>
                        <input type="text" name="first_name" value="<?php echo e(old('first_name', auth()->user()->first_name)); ?>" 
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 focus:border-[#590d0d] focus:ring-2 focus:ring-[#590d0d]/10 text-xs font-semibold text-slate-800 placeholder-slate-400 bg-white outline-none transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Last Name</label>
                        <input type="text" name="last_name" value="<?php echo e(old('last_name', auth()->user()->last_name)); ?>" 
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 focus:border-[#590d0d] focus:ring-2 focus:ring-[#590d0d]/10 text-xs font-semibold text-slate-800 placeholder-slate-400 bg-white outline-none transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Employee / Admin ID</label>
                        <input type="text" name="id_number" value="<?php echo e(old('id_number', auth()->user()->id_number)); ?>" readonly placeholder="ADM-2026-001"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-100 text-xs font-mono font-bold text-slate-500 outline-none cursor-not-allowed">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Contact Number</label>
                        <input type="text" name="phone_number" value="<?php echo e(old('phone_number', auth()->user()->phone_number ?? auth()->user()->contact_number)); ?>" 
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 focus:border-[#590d0d] focus:ring-2 focus:ring-[#590d0d]/10 text-xs font-semibold text-slate-800 placeholder-slate-400 bg-white outline-none transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Email Address</label>
                    <input type="email" name="email" value="<?php echo e(old('email', auth()->user()->email)); ?>" required placeholder="admin@siatrack.edu.ph"
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-300 focus:border-[#590d0d] focus:ring-2 focus:ring-[#590d0d]/10 text-xs font-semibold text-slate-800 placeholder-slate-400 bg-white outline-none transition">
                </div>
            </div>

            <!-- SECTION 2: AUTHENTICATION & SECURITY -->
            <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-500">Authentication & Security</span>
                    <span class="text-[10px] font-semibold text-slate-400 italic">Leave new passwords blank to keep current</span>
                </div>

                <!-- Current Password (Read-only / View via Eye Icon) -->
<div>
    <div class="flex items-center justify-between mb-1">
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
            Current Password
        </label>
    </div>
    <div class="relative flex items-center">
        <input type="password" id="current_password_field" name="current_password" 
               value="<?php echo e($currentAdminPassword); ?>" 
               readonly
               class="w-full px-3.5 py-2 pr-10 rounded-xl border border-slate-200 bg-slate-100 text-xs font-mono font-bold text-slate-600 outline-none cursor-not-allowed">
        <button type="button" onclick="togglePasswordVisibility('current_password_field', this)" 
                title="Toggle visibility"
                class="absolute right-3 text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
            <i class="fa-solid fa-eye text-xs"></i>
        </button>
    </div>
</div>

                <!-- New Password Inputs -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">New Password</label>
                        <div class="relative flex items-center">
                            <input type="password" id="new_password_field" name="password" minlength="8"
                                   class="w-full px-3.5 py-2 pr-10 rounded-xl border border-slate-300 focus:border-[#590d0d] focus:ring-2 focus:ring-[#590d0d]/10 text-xs font-mono font-medium text-slate-800 placeholder-slate-400 bg-white outline-none transition">
                            <button type="button" onclick="togglePasswordVisibility('new_password_field', this)" 
                                    title="Toggle visibility"
                                    class="absolute right-3 text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                                <i class="fa-solid fa-eye text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Confirm New Password</label>
                        <div class="relative flex items-center">
                            <input type="password" id="confirm_password_field" name="password_confirmation" minlength="8"
                                   class="w-full px-3.5 py-2 pr-10 rounded-xl border border-slate-300 focus:border-[#590d0d] focus:ring-2 focus:ring-[#590d0d]/10 text-xs font-mono font-medium text-slate-800 placeholder-slate-400 bg-white outline-none transition">
                            <button type="button" onclick="togglePasswordVisibility('confirm_password_field', this)" 
                                    title="Toggle visibility"
                                    class="absolute right-3 text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                                <i class="fa-solid fa-eye text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Pinned Footer -->
        <div class="px-7 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-3 shrink-0">
            <button type="button" onclick="closeEditProfileModal()" 
                    class="px-4 py-2 rounded-xl border border-slate-300 hover:bg-slate-100 text-slate-700 text-xs font-bold cursor-pointer transition">
                Cancel
            </button>
            <button type="submit" 
                    class="px-6 py-2 rounded-xl bg-[#590d0d] hover:bg-[#460a0a] text-white text-xs font-bold shadow-md shadow-[#590d0d]/20 cursor-pointer transition active:scale-[0.98]">
                Save Changes
            </button>
        </div>
    </form>
</div>

<!-- ================= Modal: Update Success Confirmation ================= -->
<div id="successActionModal" 
     class="fixed inset-0 z-50 bg-slate-950/65 hidden items-center justify-center p-4 backdrop-blur-md transition-all duration-300"
     onclick="if(event.target === this) closeSuccessModal()">
    
    <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl border border-slate-200/90 overflow-hidden flex flex-col items-center text-center p-6 animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Animated / Highlighted Check Icon -->
        <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center text-2xl mb-4 shadow-sm ring-4 ring-emerald-50/60">
            <i class="fa-solid fa-circle-check"></i>
        </div>

        <h3 class="text-base font-black text-slate-900 tracking-tight mb-1">Update Successful</h3>
        <p id="successModalMessage" class="text-xs text-slate-500 font-medium leading-relaxed px-2 mb-6">
            <?php echo e(session('success') ?? 'Your administrator credentials have been saved successfully.'); ?>

        </p>

        <button type="button" onclick="closeSuccessModal()" 
                class="w-full py-2.5 rounded-xl bg-[#590d0d] hover:bg-[#460a0a] text-white text-xs font-bold shadow-md shadow-[#590d0d]/25 transition active:scale-[0.98] cursor-pointer">
            Continue to Dashboard
        </button>
    </div>
</div>

<!-- ================= Metric Detail Modal Container ================= -->
<?php
    $modalStudents = \Illuminate\Support\Facades\DB::table('users')
        ->where('role_id', 3)
        ->select('id', 'first_name', 'last_name', 'email', 'id_number')
        ->latest('id')->take(5)->get();

    $modalAttendance = \Illuminate\Support\Facades\Schema::hasTable('attendance_logs')
        ? \Illuminate\Support\Facades\DB::table('attendance_logs')
            ->leftJoin('users', 'attendance_logs.user_id', '=', 'users.id')
            ->select('attendance_logs.*', 'users.first_name', 'users.last_name', 'users.id_number')
            ->whereDate('attendance_logs.created_at', now()->today())
            ->latest('attendance_logs.created_at')->take(5)->get()
        : collect();

    $modalFaculty = \Illuminate\Support\Facades\DB::table('users')
        ->where('role_id', 2)
        ->select('id', 'first_name', 'last_name', 'email', 'id_number')
        ->take(5)->get();
?>

<div id="metricModalOverlay" 
     class="fixed inset-0 z-50 hidden bg-slate-950/65 backdrop-blur-md items-center justify-center p-4 sm:p-6 transition-all duration-300" 
     style="display: none;"
     onclick="if(event.target === this) closeMetricModal()">
    
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-xl w-full flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Modal Header -->
        <div class="px-7 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
            <div>
                <h3 id="modalTitleText" class="text-base font-black text-slate-900 tracking-tight"></h3>
                <p id="modalSubText" class="text-xs text-slate-500 font-medium mt-0.5"></p>
            </div>
            <button type="button" onclick="closeMetricModal()" 
                    class="w-8 h-8 rounded-lg bg-slate-200/60 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center text-base font-bold transition cursor-pointer">
                &times;
            </button>
        </div>

        <!-- Modal Body Content -->
        <div class="p-6 space-y-3.5 text-xs">
            
            <!-- 1. Students Content -->
            <div id="sec_students" class="metric-sec" style="display: none;">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-bold text-slate-500 uppercase tracking-wider text-[10px]">Recent Enrolled Students</span>
                    <span class="font-black text-[#590d0d] bg-red-50 border border-red-100 px-2 py-0.5 rounded text-[10px]"><?php echo e(count($modalStudents)); ?> student(s)</span>
                </div>
                <div class="border border-slate-200 rounded-2xl overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-black uppercase text-[10px] border-b border-slate-200">
                            <tr>
                                <th class="p-3">Student Name</th>
                                <th class="p-3">LRN / ID</th>
                                <th class="p-3">Email</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                            <?php $__empty_1 = true; $__currentLoopData = $modalStudents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-3 font-bold text-slate-900"><?php echo e($st->first_name); ?> <?php echo e($st->last_name); ?></td>
                                <td class="p-3 text-slate-500 font-mono text-[11px]"><?php echo e($st->id_number ?? 'N/A'); ?></td>
                                <td class="p-3 text-slate-500 truncate max-w-[150px]"><?php echo e($st->email); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="3" class="p-6 text-center text-slate-400 font-medium">No enrolled students found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Attendance Content -->
            <div id="sec_attendance" class="metric-sec" style="display: none;">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-bold text-slate-500 uppercase tracking-wider text-[10px]">Today's Gate Records</span>
                    <span class="font-black text-emerald-800 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded text-[10px]"><?php echo e(count($modalAttendance)); ?> tap(s)</span>
                </div>
                <div class="border border-slate-200 rounded-2xl overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-black uppercase text-[10px] border-b border-slate-200">
                            <tr>
                                <th class="p-3">Student</th>
                                <th class="p-3">Timestamp</th>
                                <th class="p-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                            <?php $__empty_1 = true; $__currentLoopData = $modalAttendance; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $at): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-3 font-bold text-slate-900"><?php echo e($at->first_name ?? 'Student'); ?> <?php echo e($at->last_name ?? ''); ?></td>
                                <td class="p-3 text-slate-500 font-mono text-[11px]"><?php echo e(\Carbon\Carbon::parse($at->created_at)->format('h:i:s A')); ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black <?php echo e(($at->status ?? '') == 'LATE' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'); ?>">
                                        <?php echo e($at->status ?? 'PRESENT'); ?>

                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="3" class="p-6 text-center text-slate-400 font-medium">No attendance taps recorded today.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Faculty Content -->
            <div id="sec_evaluation" class="metric-sec" style="display: none;">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-bold text-slate-500 uppercase tracking-wider text-[10px]">Academic Faculty</span>
                    <span class="font-black text-blue-700 bg-blue-50 border border-blue-100 px-2 py-0.5 rounded text-[10px]"><?php echo e(count($modalFaculty)); ?> instructors</span>
                </div>
                <div class="border border-slate-200 rounded-2xl overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-black uppercase text-[10px] border-b border-slate-200">
                            <tr>
                                <th class="p-3">Instructor</th>
                                <th class="p-3">Faculty ID</th>
                                <th class="p-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                            <?php $__empty_1 = true; $__currentLoopData = $modalFaculty; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-3 font-bold text-slate-900"><?php echo e($fa->first_name); ?> <?php echo e($fa->last_name); ?></td>
                                <td class="p-3 text-slate-500 font-mono text-[11px]"><?php echo e($fa->id_number ?? 'FAC-N/A'); ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-blue-100 text-blue-800 border border-blue-200">Active</span>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="3" class="p-6 text-center text-slate-400 font-medium">No instructors found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. SMS Gateway Content -->
            <div id="sec_sms" class="metric-sec" style="display: none;">
                <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 space-y-1">
                    <div class="flex items-center justify-between font-bold">
                        <span>GSM Hardware Terminal</span>
                        <span class="px-2 py-0.5 rounded bg-emerald-200 text-emerald-800 text-[10px] font-black">ONLINE</span>
                    </div>
                    <p class="text-[11px] text-emerald-800 leading-relaxed">
                        Dispatches automated SMS alerts to parent contact numbers when student attendance is confirmed at terminal gates.
                    </p>
                </div>
                <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-2 font-semibold text-slate-600">
                    <div class="flex justify-between">
                        <span>Dispatched Today:</span>
                        <span class="font-bold text-slate-900"><?php echo e($activeSMS ?? 0); ?> alerts</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Terminal Connectivity:</span>
                        <span class="font-bold text-emerald-700">Stable (100%)</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Pinned Footer -->
        <div class="px-7 py-4 border-t border-slate-100 bg-slate-50 flex justify-end shrink-0">
            <button type="button" onclick="closeMetricModal()" 
                    class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition cursor-pointer">
                Close
            </button>
        </div>
    </div>
</div>

<!-- ================= Modal Scripts ================= -->
<script>
    function openEditProfileModal() {
        const modal = document.getElementById('editProfileModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditProfileModal() {
        const modal = document.getElementById('editProfileModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openSuccessModal(message) {
        if (message) {
            document.getElementById('successModalMessage').innerText = message;
        }
        const modal = document.getElementById('successActionModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeSuccessModal() {
        const modal = document.getElementById('successActionModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function togglePasswordVisibility(fieldId, btn) {
        const input = document.getElementById(fieldId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function openMetricModal(type, title, subtitle) {
        document.querySelectorAll('.metric-sec').forEach(el => el.style.display = 'none');
        const target = document.getElementById('sec_' + type);
        if (target) target.style.display = 'block';

        document.getElementById('modalTitleText').innerText = title || 'Metric Details';
        document.getElementById('modalSubText').innerText = subtitle || '';

        const overlay = document.getElementById('metricModalOverlay');
        overlay.classList.remove('hidden');
        overlay.style.display = 'flex';
    }

    function closeMetricModal() {
        const overlay = document.getElementById('metricModalOverlay');
        overlay.classList.add('hidden');
        overlay.style.display = 'none';
    }

    // Auto-trigger kapag nag-redirect ang controller na may session('success')
    <?php if(session('profile_success')): ?>
        document.addEventListener('DOMContentLoaded', function () {
            openSuccessModal(<?php echo json_encode(session('profile_success'), 15, 512) ?>);
        });
    <?php endif; ?>
</script><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views\admin\dashboard\partials\modals.blade.php ENDPATH**/ ?>