<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_unverified_admin_roles_can_access_admin_area(): void
    {
        foreach (['admin', 'super-admin'] as $role) {
            $user = User::factory()->unverified()->create(['role' => $role]);
            $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
            Auth::logout();
        }
    }

    public function test_unverified_admin_login_goes_to_admin_dashboard(): void
    {
        foreach (['admin', 'super-admin'] as $role) {
            $user = User::factory()->unverified()->create(['role' => $role]);
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(route('admin.dashboard'));
            Auth::logout();
        }
    }

    public function test_public_admin_preview_route_is_not_exposed(): void
    {
        $this->get('/admin-preview')->assertNotFound();
        $this->assertFalse(collect(app('router')->getRoutes())->contains(fn ($route) => $route->uri() === 'admin-preview'));
    }
}
