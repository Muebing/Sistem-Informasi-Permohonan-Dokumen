<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\UploadDocumentRequest;
use App\Http\Resources\ProjectDocumentResource;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Services\ProjectDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectDocumentController extends Controller
{
    public function __construct(
        private readonly ProjectDocumentService $documentService,
    ) {}

    public function store(UploadDocumentRequest $request, Project $project): JsonResponse
    {
        $this->authorize('uploadDocument', $project);

        $documents = [];

        foreach ($request->file('documents', []) as $file) {
            $documents[] = $this->documentService->store($project, $file, $request->user());
        }

        return response()->json([
            'documents' => ProjectDocumentResource::collection($documents),
        ], 201);
    }

    public function destroy(Request $request, Project $project, ProjectDocument $document): JsonResponse
    {
        abort_unless($document->project_id === $project->id, 404, 'Document not found.');

        $this->authorize('deleteDocument', $project);

        $this->documentService->delete($request->user(), $document);

        return response()->json(['message' => 'Document deleted successfully.']);
    }

    public function download(Request $request, Project $project, ProjectDocument $document): StreamedResponse
    {
        abort_unless($document->project_id === $project->id, 404, 'Document not found.');

        $this->authorize('view', $project);

        if (! Storage::disk('documents')->exists($document->path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('documents')->download($document->path, $document->original_name);
    }
}
