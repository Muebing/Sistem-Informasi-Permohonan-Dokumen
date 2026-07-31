<?php

namespace App\Services;

use App\Jobs\ProcessDocumentJob;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectDocumentService
{
    public function __construct(
        private readonly ActivityService $activity,
    ) {}

    /**
     * Store an uploaded document and enqueue its background processing.
     */
    public function store(Project $project, UploadedFile $file, User $uploader): ProjectDocument
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $storedName = Str::uuid()->toString().'.'.$extension;

        $path = $file->storeAs(
            (string) $project->id,
            $storedName,
            ['disk' => 'documents'],
        );

        if (! is_string($path)) {
            throw new \RuntimeException('Failed to store uploaded document.');
        }

        $document = ProjectDocument::create([
            'project_id' => $project->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $uploader->id,
        ]);

        ProcessDocumentJob::dispatch($document);

        $this->activity->log($uploader, 'uploaded document', $project);

        return $document;
    }

    /**
     * Delete a document record and its physical file.
     */
    public function delete(User $user, ProjectDocument $document): void
    {
        Storage::disk('documents')->delete($document->path);

        $document->delete();

        $this->activity->log($user, 'deleted document', $document->project);
    }
}
