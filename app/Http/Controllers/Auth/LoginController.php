<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Show the unified login / welcome page.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Unified login handler: ID Number or Email with hashed or plain-text password for all roles.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginInput = trim($request->username);

        // Students use usernames; admin and faculty may continue using email.
        $user = User::where(function ($query) use ($loginInput) {
                        $query->whereRaw('LOWER(username) = ?', [strtolower($loginInput)])
                            ->orWhereRaw('LOWER(email) = ?', [strtolower($loginInput)]);
                    })
                    ->first();

        // Older seeded/imported accounts may contain plaintext passwords. Only call
        // Hash::check for recognized password hashes because Laravel throws for
        // unsupported formats instead of returning false.
        $passwordMatches = false;
        $passwordWasPlaintext = false;
        if ($user) {
            $storedPassword = (string) $user->password;
            $passwordInfo = password_get_info($storedPassword);

            if (($passwordInfo['algo'] ?? 0) !== 0) {
                $passwordMatches = Hash::check($request->password, $storedPassword);
            } else {
                $passwordMatches = hash_equals($storedPassword, (string) $request->password);
                $passwordWasPlaintext = $passwordMatches;
            }
        }

        if ($user && $passwordMatches) {
            if ($passwordWasPlaintext) {
                $user->forceFill(['password' => Hash::make($request->password)])->save();
            }

            // Check kung active ang account
            if (isset($user->is_active) && ! $user->is_active) {
                \App\Models\AuditLog::record(
                    action: 'Failed Login',
                    module: 'Authentication',
                    status: 'Failed',
                    description: "Login attempt on deactivated account: {$user->email}.",
                    user: $user
                );
                return back()->withErrors([
                    'username' => 'Your account is deactivated. Please contact the administrator.'
                ])->onlyInput('username');
            }

            // Manu-manong i-authenticate ang session
            Auth::login($user, $request->filled('remember'));
            $request->session()->regenerate();

            \App\Models\AuditLog::record(
                action: 'Login',
                module: 'Authentication',
                status: 'Success',
                description: "User {$user->first_name} {$user->last_name} logged in successfully.",
                user: $user
            );

            // Resolve management separately so role 4 never receives administrator access.
            $actualRole = 'student';

            $roleName = is_object($user->role) ? strtolower((string) $user->role->name) : strtolower((string) $user->role);
            if ((int) $user->role_id === 1) {
                $actualRole = 'admin';
            } elseif ((int) $user->role_id === 4) {
                $actualRole = 'management';
            } elseif ((int) $user->role_id === 2) {
                $actualRole = 'teacher';
            } elseif ((int) $user->role_id === 3) {
                $actualRole = 'student';
            } elseif ($roleName === 'admin') {
                $actualRole = 'admin';
            } elseif (in_array($roleName, ['management', 'director', 'superadmin_viewer'], true)) {
                $actualRole = 'management';
            } elseif (in_array($roleName, ['teacher', 'faculty'], true)) {
                $actualRole = 'teacher';
            }

            if ($actualRole === 'teacher') {
                $request->session()->put('attendance_login_access', true);
            }

            // Redirect sa kani-kanilang dashboard
            return match ($actualRole) {
                'admin'   => redirect()->route('admin.dashboard'),
                'management' => redirect()->route('management.dashboard'),
                'teacher' => redirect()->route('attendance.live'),
                'student' => redirect()->route('student.dashboard'),
                default   => redirect('/'),
            };
        }

        // Kapag hindi nagtugma
        \App\Models\AuditLog::record(
            action: 'Failed Login',
            module: 'Authentication',
            status: 'Failed',
            description: "Failed login attempt with identifier '{$loginInput}'."
        );

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->onlyInput('username');
    }

    /**
     * Logout handler.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            \App\Models\AuditLog::record(
                action: 'Logout',
                module: 'Authentication',
                status: 'Success',
                description: "User {$user->first_name} {$user->last_name} logged out.",
                user: $user
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}