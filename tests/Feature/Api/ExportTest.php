<?php

namespace Tests\Feature\Api;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewer_can_export_excel(): void
    {
        $this->reviewer();
        Project::factory()->count(3)->create();

        $response = $this->get('/api/v1/projects/export/excel');

        $response->assertOk();
        $this->assertStringContainsString('spreadsheet', $response->headers->get('content-type'));
    }

    public function test_reviewer_can_export_pdf(): void
    {
        $this->reviewer();
        Project::factory()->count(2)->create();

        $response = $this->get('/api/v1/projects/export/pdf');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_applicant_cannot_export_projects(): void
    {
        $this->applicant();

        $this->get('/api/v1/projects/export/excel')->assertForbidden();
        $this->get('/api/v1/projects/export/pdf')->assertForbidden();
    }

    public function test_applicant_can_download_own_project_pdf(): void
    {
        $applicant = $this->applicant();
        $project = Project::factory()->create([
            'applicant_id' => $applicant->id,
            'status' => ProjectStatus::Approved,
        ]);

        $response = $this->getJson("/api/v1/projects/{$project->id}/pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_applicant_cannot_download_others_project_pdf(): void
    {
        $this->applicant();
        $project = Project::factory()->create();

        $this->getJson("/api/v1/projects/{$project->id}/pdf")->assertForbidden();
    }

    public function test_excel_export_respects_filters(): void
    {
        $this->reviewer();
        Project::factory()->create(['status' => ProjectStatus::Approved]);
        Project::factory()->create(['status' => ProjectStatus::Rejected]);

        $this->get('/api/v1/projects/export/excel?status=approved')->assertOk();
    }
}
