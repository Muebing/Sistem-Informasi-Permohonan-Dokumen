<?php

namespace Tests\Feature\Api;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_applicant_sees_own_dashboard_summary(): void
    {
        $applicant = $this->applicant();
        Project::factory()->count(2)->create(['applicant_id' => $applicant->id]);
        Project::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/dashboard')->assertOk();

        $dashboard = $response->json('dashboard');
        $this->assertEquals(2, $dashboard['summary']['draft']);
        $this->assertCount(12, $dashboard['monthly']);
        $this->assertArrayHasKey('recent_activity', $dashboard);
    }

    public function test_reviewer_sees_global_dashboard_summary(): void
    {
        $this->reviewer();
        Project::factory()->create(['status' => ProjectStatus::Approved]);
        Project::factory()->create(['status' => ProjectStatus::Submitted]);

        $response = $this->getJson('/api/v1/dashboard')->assertOk();

        $dashboard = $response->json('dashboard');
        $this->assertEquals(1, $dashboard['summary']['approved']);
        $this->assertEquals(1, $dashboard['summary']['submitted']);
        $this->assertCount(12, $dashboard['monthly']);
        $this->assertArrayHasKey('recent_reviews', $dashboard);
    }

    public function test_reviewer_sees_reviewer_dashboard(): void
    {
        $this->reviewer();

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('dashboard.summary.draft', 0);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    }
}
