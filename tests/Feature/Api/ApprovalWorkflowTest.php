<?php

namespace Tests\Feature\Api;

use App\Enums\ProjectStatus;
use App\Mail\ReviewOutcomeNotification;
use App\Models\Project;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function draftProject(): Project
    {
        $applicant = $this->applicant();
        $project = Project::factory()->create(['applicant_id' => $applicant->id]);
        $project->documents()->create([
            'original_name' => 'berkas.pdf',
            'stored_name' => 'uuid.pdf',
            'path' => '1/uuid.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1000,
        ]);

        return $project;
    }

    private function submittedProject(): Project
    {
        $project = $this->draftProject();
        $project->update(['status' => ProjectStatus::Submitted, 'submitted_at' => now()]);

        return $project;
    }

    public function test_project_cannot_be_submitted_without_documents(): void
    {
        $applicant = $this->applicant();
        $project = Project::factory()->create(['applicant_id' => $applicant->id]);

        $this->postJson("/api/v1/projects/{$project->id}/submit")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Upload at least one document before submitting.');
    }

    public function test_applicant_can_submit_project(): void
    {
        $applicant = $this->applicant();
        $project = Project::factory()->create(['applicant_id' => $applicant->id]);
        $project->documents()->create([
            'original_name' => 'berkas.pdf',
            'stored_name' => 'uuid.pdf',
            'path' => '1/uuid.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1000,
        ]);

        $this->postJson("/api/v1/projects/{$project->id}/submit")
            ->assertOk()
            ->assertJsonPath('project.status.value', 'submitted');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'submitted',
        ]);
        $this->assertNotNull($project->fresh()->submitted_at);
        $this->assertDatabaseHas('approval_logs', [
            'project_id' => $project->id,
            'action' => 'submit',
        ]);
    }

    public function test_only_owner_can_submit(): void
    {
        $project = $this->draftProject();

        $other = $this->applicant();
        $this->actingAs($other, 'sanctum');

        $this->postJson("/api/v1/projects/{$project->id}/submit")->assertForbidden();
    }

    public function test_reviewer_can_start_review_on_submitted_project(): void
    {
        $project = $this->submittedProject();
        $reviewer = $this->reviewer();

        $this->postJson("/api/v1/projects/{$project->id}/start-review")
            ->assertOk()
            ->assertJsonPath('project.status.value', 'under_review');

        $this->assertDatabaseHas('approval_logs', [
            'project_id' => $project->id,
            'user_id' => $reviewer->id,
            'action' => 'review',
        ]);
    }

    public function test_applicant_cannot_start_review(): void
    {
        $this->applicant();
        $project = $this->submittedProject();

        $this->postJson("/api/v1/projects/{$project->id}/start-review")->assertForbidden();
    }

    public function test_reviewer_can_approve_project(): void
    {
        Mail::fake();
        $project = $this->submittedProject();
        $reviewer = $this->reviewer();

        $response = $this->postJson('/api/v1/reviews', [
            'project_id' => $project->id,
            'status' => 'approved',
            'notes' => 'Dokumen lengkap dan valid.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('review.status.value', 'approved')
            ->assertJsonPath('review.reviewer.id', $reviewer->id);

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'approved']);
        $this->assertDatabaseHas('reviews', [
            'project_id' => $project->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('approval_logs', [
            'project_id' => $project->id,
            'action' => 'approve',
        ]);

        Mail::assertQueued(ReviewOutcomeNotification::class);
    }

    public function test_reviewer_can_request_revision(): void
    {
        $project = $this->submittedProject();
        $this->reviewer();

        $this->postJson('/api/v1/reviews', [
            'project_id' => $project->id,
            'status' => 'revision',
            'notes' => 'Lengkapi lampiran surat kuasa.',
        ])->assertCreated();

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'revision']);
    }

    public function test_reviewer_can_reject_project(): void
    {
        $project = $this->submittedProject();
        $this->reviewer();

        $this->postJson('/api/v1/reviews', [
            'project_id' => $project->id,
            'status' => 'rejected',
            'notes' => 'Persyaratan tidak terpenuhi.',
        ])->assertCreated();

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'rejected']);
    }

    public function test_applicant_cannot_create_review(): void
    {
        $project = $this->submittedProject();
        $this->applicant();

        $this->postJson('/api/v1/reviews', [
            'project_id' => $project->id,
            'status' => 'approved',
        ])->assertForbidden();
    }

    public function test_review_requires_valid_decision(): void
    {
        $project = $this->submittedProject();
        $this->reviewer();

        $this->postJson('/api/v1/reviews', [
            'project_id' => $project->id,
            'status' => 'draft',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_cannot_review_project_not_awaiting_review(): void
    {
        $applicant = $this->applicant();
        $project = Project::factory()
            ->withStatus(ProjectStatus::Draft)
            ->create(['applicant_id' => $applicant->id]);
        $this->reviewer();

        $this->postJson('/api/v1/reviews', [
            'project_id' => $project->id,
            'status' => 'approved',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('project_id');
    }

    public function test_applicant_can_resubmit_after_revision(): void
    {
        $project = $this->submittedProject();
        $this->reviewer();

        $this->postJson('/api/v1/reviews', [
            'project_id' => $project->id,
            'status' => 'revision',
            'notes' => 'Perbaiki dokumen.',
        ])->assertCreated();

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'revision']);

        $applicant = $project->applicant;
        $this->actingAs($applicant, 'sanctum');

        $this->postJson("/api/v1/projects/{$project->id}/submit")
            ->assertOk()
            ->assertJsonPath('project.status.value', 'submitted');
    }

    public function test_applicant_sees_reviews_on_own_project(): void
    {
        $project = $this->submittedProject();
        $reviewer = $this->reviewer();
        Review::create([
            'project_id' => $project->id,
            'reviewer_id' => $reviewer->id,
            'status' => ProjectStatus::Approved,
            'notes' => 'Disetujui.',
        ]);
        $project->update(['status' => ProjectStatus::Approved]);

        $this->actingAs($project->applicant, 'sanctum');

        $this->getJson("/api/v1/projects/{$project->id}")
            ->assertOk()
            ->assertJsonCount(1, 'project.reviews')
            ->assertJsonPath('project.reviews.0.status.value', 'approved');
    }

    public function test_project_history_records_approval_trail(): void
    {
        $project = $this->draftProject();
        $this->actingAs($project->applicant, 'sanctum');

        $this->postJson("/api/v1/projects/{$project->id}/submit")->assertOk();

        $this->reviewer();

        $this->postJson('/api/v1/reviews', [
            'project_id' => $project->id,
            'status' => 'approved',
            'notes' => 'OK.',
        ])->assertCreated();

        $this->actingAs($project->applicant, 'sanctum');

        $response = $this->getJson("/api/v1/projects/{$project->id}/history")->assertOk();

        $actions = collect($response->json('history'))->pluck('action');
        $this->assertTrue($actions->contains('submit'));
        $this->assertTrue($actions->contains('approve'));
    }
}
