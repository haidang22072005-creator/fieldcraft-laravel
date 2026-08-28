<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_public_admin_preview_route_is_not_exposed(): void
    {
        $this->get('/admin-preview')->assertNotFound();
        $this->assertFalse(collect(app('router')->getRoutes())->contains(fn ($route) => $route->uri() === 'admin-preview'));
    }
}
