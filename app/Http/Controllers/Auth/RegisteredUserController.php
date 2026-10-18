<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        $role = $request->query('role', 'student');
        return view('auth.register', compact('role'));
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $roleId = (int)$request->input('role_id', 3);
        $isFaculty = ($roleId === 2);

        // 1. Validation Rules
        $rules = [
            'id_number'           => ['required', 'string', 'max:50', 'unique:' . User::class],
            'first_name'          => ['required', 'string', 'max:255'],
            'last_name'           => ['required', 'string', 'max:255'],
            'email'               => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'gender'              => ['required', 'integer'],
            'phone_number'        => ['nullable', 'string', 'max:20'],
            'password'            => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        // Conditional rules for student vs faculty
        if ($isFaculty) {
            $rules['photo'] = ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'];
        } else {
            $rules['grade_level']         = ['required', 'integer'];
            $rules['track']               = ['required', 'integer'];
            $rules['section']             = ['required', 'integer'];
            $rules['parent_name']         = ['nullable', 'string', 'max:255'];
            $rules['parent_phone_number'] = ['required', 'string', 'max:20'];
        }

        $request->validate($rules);

        // 2. Handle Faculty Photo Upload
        $photoPath = null;
        if ($isFaculty && $request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('profile_photos', 'public');
        }

        // 3. Create User Record
        $user = User::create([
            'id_number'           => $request->id_number,
            'role_id'             => $roleId, 
            'first_name'          => $request->first_name,
            'last_name'           => $request->last_name,
            'gender'              => (int)$request->gender,
            'email'               => $request->email,
            'phone_number'        => $request->phone_number,
            'grade_level'         => !$isFaculty ? (int)$request->grade_level : null,
            'track'               => !$isFaculty ? (int)$request->track : null,
            'section'             => !$isFaculty ? (int)$request->section : null,
            'parent_name'         => !$isFaculty ? $request->parent_name : null,
            'parent_phone_number' => !$isFaculty ? $request->parent_phone_number : null,
            'photo'               => $photoPath,
            'password'            => Hash::make($request->password),
            'is_active'           => 1,
        ]);

        event(new Registered($user));

        // 4. Redirect to Login Page instead of logging in automatically
        $roleSlug = $isFaculty ? 'teacher' : 'student';

        return redirect()->route('login.portal', ['role' => $roleSlug])
            ->with('success', 'Registration completed successfully! Please sign in with your credentials.');
    }
}