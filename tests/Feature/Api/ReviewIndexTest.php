<?php

namespace Tests\Feature\Api;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewer_sees_pending_queue_and_history(): void
    {
        $reviewer = $this->reviewer();
        Project::factory()->create(['status' => ProjectStatus::Submitted, 'submitted_at' => now()]);
        Project::factory()->create(['status' => ProjectStatus::UnderReview, 'submitted_at' => now()]);
        Project::factory()->create(['status' => ProjectStatus::Draft]);

        $reviewed = Project::factory()->create(['status' => ProjectStatus::Approved, 'submitted_at' => now()]);
        Review::create([
            'project_id' => $reviewed->id,
            'reviewer_id' => $reviewer->id,
            'status' => ProjectStatus::Approved,
            'notes' => 'OK.',
        ]);

        $response = $this->getJson('/api/v1/reviews')->assertOk();

        $this->assertCount(2, $response->json('pending'));
        $this->assertCount(1, $response->json('reviews'));
    }

    public function test_reviewer_pending_queue_excludes_drafts(): void
    {
        $this->reviewer();
        Project::factory()->create(['status' => ProjectStatus::Draft]);

        $this->getJson('/api/v1/reviews')
            ->assertOk()
            ->assertJsonCount(0, 'pending');
    }

    public function test_applicant_sees_only_own_reviews(): void
    {
        $applicant = $this->applicant();
        $own = Project::factory()->create(['applicant_id' => $applicant->id, 'status' => ProjectStatus::Approved]);
        $reviewer = $this->reviewer();
        Review::create([
            'project_id' => $own->id,
            'reviewer_id' => $reviewer->id,
            'status' => ProjectStatus::Approved,
            'notes' => 'Setuju.',
        ]);

        $others = Project::factory()->create(['status' => ProjectStatus::Rejected]);
        Review::create([
            'project_id' => $others->id,
            'reviewer_id' => $reviewer->id,
            'status' => ProjectStatus::Rejected,
            'notes' => 'Tolak.',
        ]);

        $this->actingAs($applicant, 'sanctum');

        $response = $this->getJson('/api/v1/reviews')->assertOk();

        $this->assertCount(1, $response->json('reviews'));
        $this->assertEquals($own->id, $response->json('reviews.0.project.id'));
    }

    public function test_guest_cannot_view_reviews(): void
    {
        $this->getJson('/api/v1/reviews')->assertUnauthorized();
    }
}
