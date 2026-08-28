<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::query()->create(array_merge([
            'name' => 'Verification User',
            'email' => 'user'.uniqid().'@example.test',
            'password' => 'Password123!',
            'role' => 'customer',
        ], $attributes));
    }

    public function test_registered_user_is_unverified_and_receives_verification_email(): void
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'New User', 'email' => 'new@example.test',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_valid_signed_link_verifies_user(): void
    {
        $user = $this->user();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->actingAs($user)->get($url)->assertRedirect(route('settings'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))
            ->assertForbidden();
    }

    public function test_resend_verification_email_works(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->actingAs($user)->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_resend_verification_email_is_throttled(): void
    {
        Notification::fake();
        $user = $this->user();

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->actingAs($user)->post(route('verification.send'))->assertRedirect();
        }

        $this->actingAs($user)->post(route('verification.send'))->assertStatus(429);
    }

    public function test_verified_route_is_blocked_before_verification_and_accessible_after(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get(route('purchases'))->assertRedirect(route('verification.notice'));

        $user->markEmailAsVerified();

        $this->actingAs($user)->get(route('purchases'))->assertOk();
    }
}
