<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_as_applicant(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'roles'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'budi@example.com']);

        $user = User::where('email', 'budi@example.com')->first();
        $this->assertTrue($user->hasRole('applicant'));
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $user->assignRole('applicant');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token']);

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_registration_requires_confirmed_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = $this->applicant();

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.roles', ['applicant']);
    }

    public function test_user_can_update_profile(): void
    {
        $user = $this->applicant();

        $this->putJson('/api/v1/auth/profile', [
            'name' => 'Nama Baru',
            'email' => $user->email,
        ])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nama Baru']);
    }

    public function test_user_can_update_password(): void
    {
        $user = $this->applicant();

        $this->putJson('/api/v1/auth/profile', [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertOk();

        $this->assertTrue(password_verify('newpassword123', $user->fresh()->password));
    }

    public function test_forgot_password_sends_reset_link(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password reset link sent to your email.');
    }

    public function test_user_can_reset_password(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'resetpass123',
            'password_confirmation' => 'resetpass123',
        ])->assertOk();

        $this->assertTrue(password_verify('resetpass123', $user->fresh()->password));
    }

    public function test_protected_route_requires_authentication(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    }

    public function test_registration_normalizes_email(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => Str::random(8),
            'email' => 'UJI@Example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'uji@example.com']);
    }
}
