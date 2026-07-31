<?php

namespace Tests\Feature\Api;

use App\Enums\ProjectStatus;
use App\Jobs\ProcessDocumentJob;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_applicant_can_upload_documents(): void
    {
        Storage::fake('documents');
        $applicant = $this->applicant();
        $project = Project::factory()->create(['applicant_id' => $applicant->id]);

        $file = UploadedFile::fake()->create('berkas.pdf', 100, 'application/pdf');

        $response = $this->postJson("/api/v1/projects/{$project->id}/documents", [
            'documents' => [$file],
        ]);

        $response->assertCreated()
            ->assertJsonCount(1, 'documents');

        Storage::disk('documents')->assertExists($response->json('documents.0.path'));

        $this->assertDatabaseHas('project_documents', [
            'project_id' => $project->id,
            'original_name' => 'berkas.pdf',
        ]);
    }

    public function test_upload_dispatches_processing_job(): void
    {
        Storage::fake('documents');
        Queue::fake();
        $applicant = $this->applicant();
        $project = Project::factory()->create(['applicant_id' => $applicant->id]);

        $this->postJson("/api/v1/projects/{$project->id}/documents", [
            'documents' => [UploadedFile::fake()->create('berkas.pdf', 100, 'application/pdf')],
        ])->assertCreated();

        Queue::assertPushed(ProcessDocumentJob::class);
    }

    public function test_applicant_cannot_upload_to_others_project(): void
    {
        Storage::fake('documents');
        $this->applicant();
        $project = Project::factory()->create();

        $this->postJson("/api/v1/projects/{$project->id}/documents", [
            'documents' => [UploadedFile::fake()->create('berkas.pdf', 100, 'application/pdf')],
        ])->assertForbidden();
    }

    public function test_upload_rejects_non_pdf_doc_docx(): void
    {
        Storage::fake('documents');
        $applicant = $this->applicant();
        $project = Project::factory()->create(['applicant_id' => $applicant->id]);

        $this->postJson("/api/v1/projects/{$project->id}/documents", [
            'documents' => [UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload')],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('documents.0');
    }

    public function test_upload_rejects_files_larger_than_5mb(): void
    {
        Storage::fake('documents');
        $applicant = $this->applicant();
        $project = Project::factory()->create(['applicant_id' => $applicant->id]);

        $this->postJson("/api/v1/projects/{$project->id}/documents", [
            'documents' => [UploadedFile::fake()->create('besar.pdf', 6000, 'application/pdf')],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('documents.0');
    }

    public function test_applicant_can_delete_own_document_in_draft(): void
    {
        Storage::fake('documents');
        $applicant = $this->applicant();
        $project = Project::factory()->create(['applicant_id' => $applicant->id]);
        $document = ProjectDocument::factory()->create(['project_id' => $project->id]);

        $this->deleteJson("/api/v1/projects/{$project->id}/documents/{$document->id}")
            ->assertOk();

        $this->assertDatabaseMissing('project_documents', ['id' => $document->id]);
    }

    public function test_document_download_requires_project_view_access(): void
    {
        Storage::fake('documents');
        $applicant = $this->applicant();
        $project = Project::factory()->create(['applicant_id' => $applicant->id]);

        $document = ProjectDocument::factory()->create([
            'project_id' => $project->id,
            'path' => 'files/surat.pdf',
        ]);

        Storage::disk('documents')->put('files/surat.pdf', 'content');

        $this->getJson("/api/v1/projects/{$project->id}/documents/{$document->id}/download")
            ->assertOk()
            ->assertDownload($document->original_name);
    }

    public function test_applicant_cannot_download_others_document(): void
    {
        Storage::fake('documents');
        $this->applicant();
        $project = Project::factory()->create();
        $document = ProjectDocument::factory()->create(['project_id' => $project->id]);

        $this->getJson("/api/v1/projects/{$project->id}/documents/{$document->id}/download")
            ->assertForbidden();
    }

    public function test_cannot_upload_documents_after_submission(): void
    {
        Storage::fake('documents');
        $applicant = $this->applicant();
        $project = Project::factory()
            ->withStatus(ProjectStatus::Submitted)
            ->create(['applicant_id' => $applicant->id]);

        $this->postJson("/api/v1/projects/{$project->id}/documents", [
            'documents' => [UploadedFile::fake()->create('terlambat.pdf', 100, 'application/pdf')],
        ])->assertForbidden();
    }
}
