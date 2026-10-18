<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_root_redirects_guests_to_login(): void
    {
        $this->get('/')
            ->assertRedirect('/login');
    }

    public function test_guest_is_redirected_to_login_from_protected_pages(): void
    {
        $this->get('/student/dashboard')
            ->assertRedirect('/login');

        $this->get('/evaluations')
            ->assertRedirect('/login');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_teacher_login_lands_on_live_attendance_with_dashboard_access(): void
    {
        $teacher = User::query()->create([
            'id_number' => 'TCH-2026-001',
            'username' => 'TCH-2026-001',
            'role_id' => 2,
            'first_name' => 'Taylor',
            'last_name' => 'Teacher',
            'email' => 'teacher@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'TCH-2026-001',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($teacher);
        $response->assertRedirect(route('attendance.live', absolute: false));

        $this->get(route('attendance.live', absolute: false))
            ->assertOk()
            ->assertSee(route('teacher.dashboard', absolute: false), false)
            ->assertDontSee('Confirm your identity')
            ->assertDontSee('Awaiting card tap...');

        $this->get(route('attendance.live', absolute: false))
            ->assertOk()
            ->assertSee('Confirm your identity')
            ->assertSee('attendance_password_toggle')
            ->assertSee('fa-eye');

        $this->get('/dashboard')
            ->assertRedirect(route('attendance.live', absolute: false));
    }

    public function test_unknown_teacher_evaluation_routes_redirect_to_dashboard(): void
    {
        $teacher = User::query()->create([
            'id_number' => 'TCH-2026-002',
            'username' => 'TCH-2026-002',
            'role_id' => 2,
            'first_name' => 'Taylor',
            'last_name' => 'Teacher',
            'email' => 'teacher2@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->actingAs($teacher)
            ->get('/teacher/evaluations/results')
            ->assertRedirect(route('dashboard'));

        $this->actingAs($teacher)
            ->get('/teacher/evaluation-report')
            ->assertRedirect(route('dashboard'));

        $this->actingAs($teacher)
            ->get('/evaluations/results')
            ->assertRedirect(route('dashboard'));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
