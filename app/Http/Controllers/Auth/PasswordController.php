<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        // 1. Validation: Ginawang plain string ang current_password para maiwasan ang Bcrypt crash
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'string'],
            'password'         => ['required', Password::defaults(), 'confirmed'],
        ]);

        // 2. Direct string comparison para sa kasalukuyang plain-text password
        if ($user->password !== $validated['current_password']) {
            return back()->withErrors([
                'current_password' => 'The provided password does not match your current password.'
            ], 'updatePassword');
        }

        // 3. I-save ang bagong password nang walang Hash::make()
        $user->update([
            'password' => $validated['password'],
        ]);

        return back()->with('status', 'password-updated');
    }
}