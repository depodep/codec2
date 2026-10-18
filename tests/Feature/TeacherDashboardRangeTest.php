<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherDashboardRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_and_monthly_dashboard_ranges_use_calendar_boundaries(): void
    {
        $teacher = User::query()->create([
            'id_number' => 'RANGE-TEACHER',
            'role_id' => 2,
            'first_name' => 'Test',
            'last_name' => 'Teacher',
            'email' => 'range.teacher@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $selectedDate = Carbon::create(2026, 10, 16);
        DB::table('academic_periods')->insert([
            'school_year' => '2026-2027',
            'semester' => 'Test',
            'is_active' => true,
            'start_date' => $selectedDate->toDateString(),
            'end_date' => $selectedDate->copy()->addYear()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->getJson(route('teacher.dashboard', [
                'range' => 'weekly',
                'date' => $selectedDate->toDateString(),
            ], false))
            ->assertOk()
            ->assertJsonPath('rangeStartFormatted', $selectedDate->copy()->startOfWeek(Carbon::MONDAY)->format('M d, Y'))
            ->assertJsonPath('rangeEndFormatted', $selectedDate->copy()->endOfWeek(Carbon::SUNDAY)->format('M d, Y'));

        $this->actingAs($teacher)
            ->getJson(route('teacher.dashboard', [
                'range' => 'monthly',
                'date' => $selectedDate->toDateString(),
            ], false))
            ->assertOk()
            ->assertJsonPath('rangeStartFormatted', $selectedDate->copy()->startOfMonth()->format('M d, Y'))
            ->assertJsonPath('rangeEndFormatted', $selectedDate->copy()->endOfMonth()->format('M d, Y'));
    }
}
