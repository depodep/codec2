<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIATRACK Portal - Southern Isabela Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#d1d5db] min-h-screen flex items-center justify-center p-4">

    <div class="relative bg-white w-full max-w-sm rounded-[36px] p-8 pt-14 shadow-2xl border border-gray-100 flex flex-col items-center">
        
        <!-- Official SIA Seal Logo -->
        <div class="absolute -top-14 w-28 h-28 rounded-full bg-white p-1 shadow-xl border-2 border-red-500 flex items-center justify-center">
            <img src="{{ asset('images/sia-logo.png') }}" alt="SIA Official Seal" class="w-full h-full object-contain rounded-full">
        </div>

        <h1 class="text-2xl font-black text-gray-900 tracking-tight text-center mt-2">SIATRACK Portal</h1>
        <p class="text-xs text-gray-500 font-semibold mt-0.5 mb-6 text-center">Southern Isabela Academy</p>

        <!-- Error Alert -->
        @if ($errors->any())
            <div class="w-full mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-2xl flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-sm text-red-600 flex-shrink-0"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- Single Unified Login Form -->
        <form action="{{ route('login.submit') }}" method="POST" class="w-full space-y-4">
            @csrf

            <div>
                <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                    Email Address
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400">
                        <i class="fa-solid fa-envelope text-sm"></i>
                    </span>
                    <input type="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           required 
                           autofocus
                           autocomplete="email" 
                           placeholder="Enter your email"
                           class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:bg-white transition">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                    Password
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400">
                        <i class="fa-solid fa-lock text-sm"></i>
                    </span>
                    <input type="password" 
                           name="password" 
                           id="passwordInput" 
                           required 
                           autocomplete="current-password" 
                           placeholder="••••••••"
                           class="w-full pl-10 pr-12 py-3 bg-gray-50 border border-gray-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:bg-white transition">
                    <button type="button" 
                            onclick="togglePasswordVisibility()" 
                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600">
                        <i class="fa-regular fa-eye" id="passwordToggleIcon"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs pt-1 pb-1">
                <label class="flex items-center gap-2 cursor-pointer text-gray-600 select-none">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded text-red-600 focus:ring-red-500 border-gray-300">
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" 
                    class="w-full py-3.5 px-5 rounded-2xl bg-[#800000] hover:bg-[#660000] text-white font-black text-sm flex items-center justify-center gap-2 shadow-lg transition transform active:scale-95">
                <i class="fa-solid fa-right-to-bracket text-base"></i>
                <span>SIGN IN</span>
            </button>
        </form>

    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('passwordInput');
            const icon = document.getElementById('passwordToggleIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>