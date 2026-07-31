<?php

namespace Tests\Feature\Api;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_applicant_can_create_a_project(): void
    {
        $applicant = $this->applicant();

        $response = $this->postJson('/api/v1/projects', [
            'project_name' => 'Permohonan Sertifikat Tanah',
            'description' => 'Pengajuan dokumen sertifikat tanah.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('project.status.value', 'draft')
            ->assertJsonPath('project.applicant.id', $applicant->id);

        $this->assertDatabaseHas('projects', [
            'applicant_id' => $applicant->id,
            'project_name' => 'Permohonan Sertifikat Tanah',
            'status' => 'draft',
        ]);
    }

    public function test_project_code_is_generated_on_create(): void
    {
        $this->applicant();

        $response = $this->postJson('/api/v1/projects', [
            'project_name' => 'Permohonan IMB',
        ]);

        $this->assertMatchesRegularExpression(
            '/^SPRD-\d{4}-\d{5}$/',
            $response->json('project.project_code'),
        );
    }

    public function test_reviewer_cannot_create_a_project(): void
    {
        $this->reviewer();

        $this->postJson('/api/v1/projects', [
            'project_name' => 'Tidak Boleh',
        ])->assertForbidden();
    }

    public function test_applicant_can_view_own_project(): void
    {
        $applicant = $this->applicant();
        $project = Project::factory()->create(['applicant_id' => $applicant->id]);

        $this->getJson("/api/v1/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('project.id', $project->id)
            ->assertJsonPath('project.project_code', $project->project_code);
    }

    public function test_applicant_cannot_view_others_project(): void
    {
        $this->applicant();
        $project = Project::factory()->create();

        $this->getJson("/api/v1/projects/{$project->id}")->assertForbidden();
    }

    public function test_reviewer_can_view_any_project(): void
    {
        $this->reviewer();
        $project = Project::factory()->create();

        $this->getJson("/api/v1/projects/{$project->id}")->assertOk();
    }

    public function test_applicant_can_update_own_draft_project(): void
    {
        $applicant = $this->applicant();
        $project = Project::factory()->create(['applicant_id' => $applicant->id]);

        $this->putJson("/api/v1/projects/{$project->id}", [
            'project_name' => 'Nama Diperbarui',
            'description' => 'Deskripsi baru.',
        ])->assertOk();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'project_name' => 'Nama Diperbarui',
        ]);
    }

    public function test_applicant_cannot_update_submitted_project(): void
    {
        $applicant = $this->applicant();
        $project = Project::factory()
            ->withStatus(ProjectStatus::Submitted)
            ->create(['applicant_id' => $applicant->id]);

        $this->putJson("/api/v1/projects/{$project->id}", [
            'project_name' => 'Tidak Boleh Ubah',
        ])->assertForbidden();
    }

    public function test_applicant_can_delete_own_draft_project(): void
    {
        $applicant = $this->applicant();
        $project = Project::factory()->create(['applicant_id' => $applicant->id]);

        $this->deleteJson("/api/v1/projects/{$project->id}")
            ->assertOk();

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_applicant_cannot_delete_others_project(): void
    {
        $this->applicant();
        $project = Project::factory()->create();

        $this->deleteJson("/api/v1/projects/{$project->id}")->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_project_listing_is_scoped_for_applicant(): void
    {
        $applicant = $this->applicant();
        Project::factory()->count(3)->create(['applicant_id' => $applicant->id]);
        Project::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/projects')->assertOk();

        $this->assertCount(3, $response->json('data'));
        $this->assertEquals(3, $response->json('meta.total'));
    }

    public function test_admin_sees_all_projects(): void
    {
        $this->admin();
        Project::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/projects')->assertOk();

        $this->assertEquals(5, $response->json('meta.total'));
    }

    public function test_project_listing_can_be_searched_and_filtered(): void
    {
        $this->admin();
        Project::factory()->create([
            'project_name' => 'Permohonan Sertifikat A',
            'status' => ProjectStatus::Approved,
        ]);
        Project::factory()->create([
            'project_name' => 'Permohonan IMB B',
            'status' => ProjectStatus::Rejected,
        ]);

        $response = $this->getJson('/api/v1/projects?search=Sertifikat&status=approved')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Permohonan Sertifikat A', $response->json('data.0.project_name'));
    }

    public function test_project_listing_can_be_sorted_by_oldest(): void
    {
        $this->admin();
        $oldest = Project::factory()->create(['created_at' => now()->subDays(10)]);
        Project::factory()->create(['created_at' => now()]);

        $response = $this->getJson('/api/v1/projects?sort=oldest')->assertOk();

        $this->assertEquals($oldest->id, $response->json('data.0.id'));
    }

    public function test_creating_project_requires_valid_payload(): void
    {
        $this->applicant();

        $this->postJson('/api/v1/projects', [
            'project_name' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('project_name');
    }

    public function test_creating_project_records_activity_log(): void
    {
        $applicant = $this->applicant();

        $this->postJson('/api/v1/projects', ['project_name' => 'Project Dengan Log']);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $applicant->id,
            'activity' => 'created project',
        ]);
    }

    public function test_guest_cannot_access_projects(): void
    {
        $this->getJson('/api/v1/projects')->assertUnauthorized();
    }
}
