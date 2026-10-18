<?php $__env->startSection('title', 'Manage Account | SIATRACK'); ?>

<?php $__env->startSection('content'); ?>
<div class="p-6 md:p-8 max-w-4xl mx-auto w-full">
    
    <!-- Page Header (SIATRACK Theme) -->
    <div class="mb-8">
        <h1 class="text-2xl font-black text-gray-800 tracking-tight flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white p-1 border-2 border-amber-300 shadow-sm flex items-center justify-center shrink-0">
                <i class="fa-solid fa-user-gear text-[#590d0d] text-lg"></i>
            </div>
            Manage Account
        </h1>
        <p class="text-sm font-semibold text-gray-500 mt-2 ml-1">
            Update your faculty profile, contact information, and security settings.
        </p>
    </div>

    <!-- Success Message Alert -->
    <?php if(session('success')): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl flex items-center gap-3 font-semibold text-sm shadow-sm">
            <i class="fa-solid fa-circle-check text-green-500 text-lg"></i>
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <!-- Profile Edit Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form action="<?php echo e(auth()->user()->role_id == 3 ? route('student.profile.update') : (Route::has('teacher.profile.update') && auth()->user()->role_id == 2 ? route('teacher.profile.update') : route('profile.update'))); ?>" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>

            <div class="p-6 md:p-8">
                
                <h2 class="text-lg font-bold text-[#590d0d] mb-6 border-b border-gray-100 pb-3 flex items-center gap-2">
                    <i class="fa-solid fa-id-card text-amber-500"></i> Personal Information
                </h2>

                <!-- Profile Photo -->
                <div class="mb-6 flex flex-col md:flex-row items-center gap-6">
                    <div class="w-24 h-24 rounded-full bg-gray-100 border-4 border-amber-100 overflow-hidden shrink-0">
                        <?php if(auth()->user()->photo): ?>
                            <img src="<?php echo e(asset('storage/' . auth()->user()->photo)); ?>" alt="Profile Photo" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-300">
                                <i class="fa-solid fa-user text-4xl"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 w-full">
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Profile Photo</label>
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/jpg" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#590d0d]/10 file:text-[#590d0d] hover:file:bg-[#590d0d]/20 transition cursor-pointer">
                        <?php $__errorArgs = ['photo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-red-500 mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
                    <!-- First Name -->
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" value="<?php echo e(old('first_name', auth()->user()->first_name)); ?>" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-sm font-medium outline-none transition">
                        <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-red-500 mt-1"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" value="<?php echo e(old('last_name', auth()->user()->last_name)); ?>" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-sm font-medium outline-none transition">
                        <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-red-500 mt-1"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- ID Number -->
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Faculty ID Number <span class="text-red-500">*</span></label>
                        <input type="text" name="id_number" value="<?php echo e(old('id_number', auth()->user()->id_number)); ?>" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-sm font-bold text-[#590d0d] outline-none transition">
                        <?php $__errorArgs = ['id_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-red-500 mt-1"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Gender -->
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Gender <span class="text-red-500">*</span></label>
                        <select name="gender" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-sm font-medium outline-none transition">
                            <option value="1" <?php echo e(old('gender', auth()->user()->gender) == 1 ? 'selected' : ''); ?>>Male</option>
                            <option value="2" <?php echo e(old('gender', auth()->user()->gender) == 2 ? 'selected' : ''); ?>>Female</option>
                        </select>
                        <?php $__errorArgs = ['gender'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-red-500 mt-1"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <h2 class="text-lg font-bold text-[#590d0d] mb-6 border-b border-gray-100 pb-3 flex items-center gap-2">
                    <i class="fa-solid fa-address-book text-amber-500"></i> Contact & Security
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                    <!-- Email -->
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="<?php echo e(old('email', auth()->user()->email)); ?>" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-sm font-medium outline-none transition">
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-red-500 mt-1"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Phone Number</label>
                        <input type="text" name="phone_number" value="<?php echo e(old('phone_number', auth()->user()->phone_number)); ?>" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-sm font-medium outline-none transition" placeholder="e.g. 09123456789">
                        <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-red-500 mt-1"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">New Password (Optional)</label>
                        <input type="password" name="password" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-sm font-medium outline-none transition" placeholder="Leave blank to keep current">
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-red-500 mt-1"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-sm font-medium outline-none transition">
                    </div>
                </div>

            </div>

            <!-- Footer Action -->
            <div class="bg-gray-50 px-6 py-4 md:px-8 border-t border-gray-100 flex items-center justify-end gap-3">
                <button type="reset" class="px-5 py-2.5 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-200 transition">
                    Reset
                </button>
                <button type="submit" class="bg-[#590d0d] hover:bg-red-950 text-amber-300 px-6 py-2.5 rounded-xl text-sm font-bold shadow-sm transition flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ped\Desktop\CodeCommanders\resources\views\profile\edit.blade.php ENDPATH**/ ?>