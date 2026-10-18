<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnknownRouteRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_route_redirects_guests_to_login(): void
    {
        $this->get('/route-that-does-not-exist')
            ->assertRedirect(route('login'));
    }

    public function test_unknown_route_redirects_authenticated_users_to_the_dashboard_router(): void
    {
        $user = User::query()->create([
            'id_number' => 'ROUTE-ADMIN',
            'role_id' => 1,
            'first_name' => 'Route',
            'last_name' => 'Admin',
            'email' => 'route-admin@example.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/admin/')
            ->assertRedirect(route('dashboard'));
    }
}
