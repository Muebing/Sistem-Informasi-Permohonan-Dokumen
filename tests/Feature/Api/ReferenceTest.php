<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_applicants(): void
    {
        $this->reviewer();
        $applicant = User::factory()->create(['name' => 'Ayu Pelamar']);
        $applicant->assignRole('applicant');

        $this->getJson('/api/v1/references?type=applicants')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Ayu Pelamar');
    }

    public function test_lists_reviewers(): void
    {
        $this->applicant();
        $reviewer = User::factory()->create(['name' => 'Bambang Reviewer']);
        $reviewer->assignRole('reviewer');

        $this->getJson('/api/v1/references?type=reviewers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Bambang Reviewer');
    }

    public function test_unknown_type_returns_empty_list(): void
    {
        $this->reviewer();

        $this->getJson('/api/v1/references?type=unknown')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
