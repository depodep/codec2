@extends('layouts.guest')

@php
    $isFaculty = in_array($role ?? 'student', ['teacher', 'faculty']);
    $roleId = $isFaculty ? 2 : 3;
    $roleTitle = $isFaculty ? 'Faculty Registration' : 'Student Registration';
    $idLabel = $isFaculty ? 'Faculty / Employee ID' : 'Student LRN';
@endphp

@section('title', $roleTitle . ' - SIATRACK')

@section('content')
<div class="relative bg-white w-full max-w-6xl rounded-[32px] shadow-2xl border-2 border-slate-300 overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[720px] my-6">
    
    <!-- Top Accent Trim -->
    <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-[#8b1818] via-amber-400 to-[#8b1818] z-30"></div>

    <!-- ================= Left Side: Institutional Banner ================= -->
    <div class="lg:col-span-5 relative bg-[#8b1818] p-10 lg:p-14 flex flex-col justify-between text-white overflow-hidden">
        <div class="absolute inset-0 login-banner-bg mix-blend-multiply opacity-40"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
        
        <!-- Top Branding -->
        <div class="relative z-10">
            <div class="flex items-center gap-2.5">
                <span class="w-3 h-3 rounded-full bg-amber-400 shadow-sm shadow-amber-300"></span>
                <h2 class="text-2xl font-black tracking-tight text-white leading-none">SIATRACK</h2>
            </div>
            <p class="text-xs text-amber-200 font-bold mt-1 tracking-wider uppercase">Southern Isabela Academy</p>
        </div>

        <!-- Center Headline -->
        <div class="relative z-10 my-auto py-8">
            <span class="px-6 py-2 bg-amber-400/20 backdrop-blur-md rounded-full text-sm font-black uppercase tracking-[0.2em] mb-4 inline-block border-2 border-amber-300/60 text-amber-300 shadow-md shadow-amber-400/10">
                BE A SIAn!
            </span>
            <h3 class="text-3xl lg:text-4xl font-black leading-tight text-white mt-1 drop-shadow-sm">
                {{ $roleTitle }}
            </h3>
            <p class="text-sm text-red-100 font-medium mt-3 max-w-md leading-relaxed">
                @if($isFaculty)
                    Register your official faculty account to manage assigned subject loads, classroom timetables, and performance evaluation metrics.
                @else
                    Register your verified student profile to enable automated NFC gate attendance tracking, timetable access, and parent alert dispatches.
                @endif
            </p>
        </div>

        <!-- Footer -->
        <div class="relative z-10 text-xs text-red-200 font-semibold border-t border-white/20 pt-4">
            © {{ date('Y') }} Southern Isabela Academy • All Rights Reserved.
        </div>
    </div>

    <!-- ================= Right Side: Registration Form ================= -->
    <div class="lg:col-span-7 p-8 lg:p-12 flex flex-col justify-center bg-white overflow-y-auto">
        
        <!-- Centered SIA Seal -->
        <div class="mx-auto w-20 h-20 rounded-full bg-white p-2.5 border-2 border-[#8b1818] ring-4 ring-amber-300/60 shadow-lg flex items-center justify-center mb-4">
            <img src="{{ asset('images/sia-logo.png') }}" alt="SIA Official Seal" class="w-full h-full object-contain">
        </div>

        <!-- Header -->
        <div class="text-center mb-6">
            <h2 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">
                {{ $roleTitle }}
            </h2>
            <p class="text-xs text-slate-600 font-bold mt-1">
                Registering as {{ $isFaculty ? 'Teaching Faculty / Instructor' : 'Enrolled Student' }}
            </p>
        </div>

        <!-- Error Banner -->
        @if($errors->any())
            <div class="mb-5 p-4 bg-red-50 border-2 border-red-300 text-red-800 text-xs font-bold rounded-2xl flex items-start gap-3 shadow-xs">
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- Form -->
        <form method="POST" action="{{ route('register.submit') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            
            <!-- Hidden Role Inputs -->
            <input type="hidden" name="role_id" value="{{ $roleId }}">
            <input type="hidden" name="role" value="{{ $isFaculty ? 'teacher' : 'student' }}">

            <!-- 1. Last Name & First Name -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-extrabold text-slate-900 uppercase tracking-wide mb-1.5">Last Name <span class="text-red-600">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required
                           class="w-full py-3 px-4 text-sm font-bold text-slate-900 border-2 border-slate-300 rounded-2xl bg-white focus:outline-none focus:border-[#8b1818]">
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-slate-900 uppercase tracking-wide mb-1.5">First Name <span class="text-red-600">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required
                           class="w-full py-3 px-4 text-sm font-bold text-slate-900 border-2 border-slate-300 rounded-2xl bg-white focus:outline-none focus:border-[#8b1818]">
                </div>
            </div>

            <!-- 2. ID Number / LRN & Email Address -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-extrabold text-slate-900 uppercase tracking-wide mb-1.5">{{ $idLabel }} <span class="text-red-600">*</span></label>
                    <input type="text" name="id_number" value="{{ old('id_number') }}" required
                           class="w-full py-3 px-4 text-sm font-mono font-bold text-slate-900 border-2 border-slate-300 rounded-2xl bg-white focus:outline-none focus:border-[#8b1818]">
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-slate-900 uppercase tracking-wide mb-1.5">Email Address <span class="text-red-600">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full py-3 px-4 text-sm font-bold text-slate-900 border-2 border-slate-300 rounded-2xl bg-white focus:outline-none focus:border-[#8b1818]">
                </div>
            </div>

            <!-- 3. Gender & Personal Contact Number -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-extrabold text-slate-900 uppercase tracking-wide mb-1.5">Gender <span class="text-red-600">*</span></label>
                    <select name="gender" required class="w-full py-3 px-4 text-sm font-bold text-slate-900 border-2 border-slate-300 rounded-2xl bg-white focus:outline-none focus:border-[#8b1818] cursor-pointer">
                        <option value="">Select Gender</option>
                        <option value="1" {{ old('gender') == '1' ? 'selected' : '' }}>Male</option>
                        <option value="2" {{ old('gender') == '2' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-slate-900 uppercase tracking-wide mb-1.5">Contact Number</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number') }}"
                           class="w-full py-3 px-4 text-sm font-bold text-slate-900 border-2 border-slate-300 rounded-2xl bg-white focus:outline-none focus:border-[#8b1818]">
                </div>
            </div>

            @if(!$isFaculty)
                <!-- ================= Student Academic Placement ================= -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 uppercase tracking-wide mb-1.5">Grade Level <span class="text-red-600">*</span></label>
                        <select name="grade_level" required class="w-full py-3 px-4 text-sm font-bold text-slate-900 border-2 border-slate-300 rounded-2xl bg-white focus:outline-none focus:border-[#8b1818] cursor-pointer">
                            <option value="">Select Grade</option>
                            <option value="11" {{ old('grade_level') == '11' ? 'selected' : '' }}>Grade 11</option>
                            <option value="12" {{ old('grade_level') == '12' ? 'selected' : '' }}>Grade 12</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 uppercase tracking-wide mb-1.5">Track <span class="text-red-600">*</span></label>
                        <select name="track" required class="w-full py-3 px-4 text-sm font-bold text-slate-900 border-2 border-slate-300 rounded-2xl bg-white focus:outline-none focus:border-[#8b1818] cursor-pointer">
                            <option value="">Select Track</option>
                            <option value="1" {{ old('track') == '1' ? 'selected' : '' }}>Academic Track</option>
                            <option value="2" {{ old('track') == '2' ? 'selected' : '' }}>Technical-Professional</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 uppercase tracking-wide mb-1.5">Section <span class="text-red-600">*</span></label>
                        <select name="section" required class="w-full py-3 px-4 text-sm font-bold text-slate-900 border-2 border-slate-300 rounded-2xl bg-white focus:outline-none focus:border-[#8b1818] cursor-pointer">
                            <option value="">Select Section</option>
                            <option value="1" {{ old('section') == '1' ? 'selected' : '' }}>Amber</option>
                            <option value="2" {{ old('section') == '2' ? 'selected' : '' }}>Crystal</option>
                            <option value="3" {{ old('section') == '3' ? 'selected' : '' }}>Pearl</option>
                            <option value="4" {{ old('section') == '4' ? 'selected' : '' }}>Turquoise</option>
                        </select>
                    </div>
                </div>

                <!-- Guardian Information -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 uppercase tracking-wide mb-1.5">Parent / Guardian Name</label>
                        <input type="text" name="parent_name" value="{{ old('parent_name') }}"
                               class="w-full py-3 px-4 text-sm font-bold text-slate-900 border-2 border-slate-300 rounded-2xl bg-white focus:outline-none focus:border-[#8b1818]">
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 uppercase tracking-wide mb-1.5">Parent Contact Number <span class="text-red-600">*</span></label>
                        <input type="text" name="parent_phone_number" value="{{ old('parent_phone_number') }}" required
                               class="w-full py-3 px-4 text-sm font-bold text-slate-900 border-2 border-slate-300 rounded-2xl bg-white focus:outline-none focus:border-[#8b1818]">
                    </div>
                </div>
            @endif

            @if($isFaculty)
                <!-- 4. Profile Photo Upload (Faculty Only) -->
                <div class="p-4 border-2 border-slate-300 rounded-2xl bg-slate-50 space-y-2">
                    <label for="photo" class="block text-xs font-extrabold text-slate-900 uppercase tracking-wide">
                        Faculty Profile Photo 
                        <span class="text-[10px] text-slate-400 font-normal lowercase">(optional, jpg/png up to 2mb)</span>
                    </label>
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-slate-200 border border-slate-300 overflow-hidden flex items-center justify-center shrink-0">
                            <img id="reg_photo_preview" src="" alt="Preview" class="hidden w-full h-full object-cover">
                            <span id="reg_photo_placeholder" class="text-[10px] font-bold text-slate-400 uppercase">Photo</span>
                        </div>
                        <div class="flex-1">
                            <input type="file" 
                                   id="photo" 
                                   name="photo" 
                                   accept="image/jpeg,image/png,image/jpg,image/webp"
                                   onchange="previewRegisterPhoto(this)"
                                   class="w-full text-xs font-semibold text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#8b1818] file:text-white hover:file:bg-[#731414] cursor-pointer">
                        </div>
                    </div>
                </div>
            @endif

            <!-- 5. Password Fields -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-extrabold text-slate-900 uppercase tracking-wide mb-1.5">Password <span class="text-red-600">*</span></label>
                    <div class="relative flex items-center">
                        <input type="password" id="reg_pass" name="password" minlength="8" required
                               class="w-full py-3 px-4 pr-12 text-sm font-bold text-slate-900 border-2 border-slate-300 rounded-2xl bg-white focus:outline-none focus:border-[#8b1818]">
                        <button type="button" onclick="togglePass('reg_pass', 'eye_icon_1')" class="absolute right-4 text-slate-400 hover:text-slate-700 transition focus:outline-none cursor-pointer">
                            <i id="eye_icon_1" class="fa-solid fa-eye text-base"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-slate-900 uppercase tracking-wide mb-1.5">Confirm Password <span class="text-red-600">*</span></label>
                    <div class="relative flex items-center">
                        <input type="password" id="reg_pass_conf" name="password_confirmation" minlength="8" required
                               class="w-full py-3 px-4 pr-12 text-sm font-bold text-slate-900 border-2 border-slate-300 rounded-2xl bg-white focus:outline-none focus:border-[#8b1818]">
                        <button type="button" onclick="togglePass('reg_pass_conf', 'eye_icon_2')" class="absolute right-4 text-slate-400 hover:text-slate-700 transition focus:outline-none cursor-pointer">
                            <i id="eye_icon_2" class="fa-solid fa-eye text-base"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" 
                    class="w-full py-4 rounded-2xl bg-[#8b1818] hover:bg-[#751414] text-white font-black text-sm tracking-wider uppercase shadow-lg shadow-red-950/20 active:scale-[0.99] mt-3 border-b-4 border-[#5e0f0f] cursor-pointer">
                Complete Registration
            </button>

            <!-- Sign In Link -->
            <div class="text-center pt-2 text-xs text-slate-600 font-semibold">
                <span>Already have an account?</span>
                <a href="{{ route('login.portal', ['role' => $role ?? 'student']) }}" class="font-extrabold text-[#8b1818] hover:text-amber-600 hover:underline ml-1 transition-colors">
                    Sign In here
                </a>
            </div>
        </form>

    </div>

</div>

@push('scripts')
<script>
    function previewRegisterPhoto(input) {
        const preview = document.getElementById('reg_photo_preview');
        const placeholder = document.getElementById('reg_photo_placeholder');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                if (placeholder) placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function togglePass(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!input || !icon) return;

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
</script>
@endpush
@endsection