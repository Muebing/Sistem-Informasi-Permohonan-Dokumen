<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /**
     * Authenticate as a user with the given role (bearer-token style).
     */
    protected function authenticate(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        Sanctum::actingAs($user);

        return $user;
    }

    protected function applicant(): User
    {
        return $this->authenticate('applicant');
    }

    protected function reviewer(): User
    {
        return $this->authenticate('reviewer');
    }

    protected function admin(): User
    {
        return $this->authenticate('admin');
    }
}
