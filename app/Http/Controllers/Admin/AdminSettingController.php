<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminSettingController extends Controller
{
    public function settingsIndex()
    {
        $settings = collect();

        if (Schema::hasTable('settings')) {
            $settings = DB::table('settings')
                ->whereIn('key', ['active_school_year'])
                ->pluck('value', 'key');
        }

        $activePeriod = Schema::hasTable('academic_periods')
            ? DB::table('academic_periods')->where('is_active', true)->first()
            : null;
        $schoolYearGroups = Schema::hasTable('academic_periods')
            ? DB::table('academic_periods')->orderByDesc('school_year')->orderBy('start_date')->get()->groupBy('school_year')
            : collect();

        return view('admin.settings.index', [
            'adminUser' => auth()->user(),
            'activePeriod' => $activePeriod,
            'schoolYearGroups' => $schoolYearGroups,
        ]);
    }

    public function updateSchoolYearDates(Request $request)
    {
        $validated = $request->validate([
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'admin_password' => ['required', 'string'],
        ], [
            'admin_password.required' => 'Admin password is required to update dates.',
        ]);

        $user = auth()->user();
        $isPasswordValid = false;
        if ($user && $user->password) {
            if ($request->admin_password === $user->password) {
                $isPasswordValid = true;
            } else {
                try {
                    if (\Illuminate\Support\Facades\Hash::check($request->admin_password, $user->password)) {
                        $isPasswordValid = true;
                    }
                } catch (\Exception $e) {
                    $isPasswordValid = false;
                }
            }
        }

        if (!$isPasswordValid) {
            return back()->withErrors(['admin_password' => 'Incorrect admin password. Changes not saved.']);
        }

        if (!Schema::hasTable('academic_periods')) {
            return back()->withErrors(['school_year' => 'The academic periods table is not available.']);
        }

        DB::table('academic_periods')
            ->where('id', $validated['academic_period_id'])
            ->update([
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'updated_at' => now(),
            ]);

        return back()->with('success', 'School year dates updated successfully.');
    }

}