<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectDetailResource;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Repositories\ProjectRepository;
use App\Services\ProjectService;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly ReviewService $reviewService,
        private readonly ProjectRepository $repository,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Project::class);

        $filters = $request->only([
            'search', 'status', 'applicant_id', 'reviewer_id', 'date_from', 'date_to', 'sort',
        ]);

        if ($request->user()->hasRole('applicant')) {
            $projects = $this->repository->forApplicant($filters, $request->user()->id);
        } else {
            $projects = $this->repository->paginate($filters);
        }

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = $this->projectService->create($request->user(), $request->validated());

        return response()->json([
            'project' => new ProjectDetailResource($project->load('documents', 'applicant', 'reviews.reviewer')),
        ], 201);
    }

    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $project->load('applicant', 'documents', 'reviews.reviewer');

        return response()->json([
            'project' => new ProjectDetailResource($project),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $this->projectService->update($request->user(), $project, $request->validated());

        return response()->json([
            'project' => new ProjectDetailResource($project->fresh(['documents', 'applicant'])),
        ]);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $this->projectService->delete($request->user(), $project);

        return response()->json(['message' => 'Project deleted successfully.']);
    }

    public function submit(Request $request, Project $project): JsonResponse
    {
        $this->authorize('submit', $project);

        if ($project->documents()->count() === 0) {
            return response()->json([
                'message' => 'Upload at least one document before submitting.',
            ], 422);
        }

        $this->projectService->submit($request->user(), $project);

        return response()->json([
            'project' => new ProjectDetailResource($project->fresh(['documents', 'applicant', 'reviews.reviewer'])),
        ]);
    }

    public function startReview(Request $request, Project $project): JsonResponse
    {
        $this->authorize('startReview', $project);

        $this->reviewService->startReview($request->user(), $project);

        return response()->json([
            'project' => new ProjectDetailResource($project->fresh(['documents', 'applicant'])),
        ]);
    }

    public function history(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $approvals = $project->approvalLogs()->with('user:id,name')->latest()->get();
        $activities = $project->activityLogs()->with('user:id,name')->latest()->get();

        $history = $approvals->map(fn ($log) => [
            'type' => 'approval',
            'action' => $log->action,
            'old_status' => $log->old_status,
            'new_status' => $log->new_status,
            'notes' => $log->notes,
            'user_name' => $log->user?->name,
            'created_at' => $log->created_at,
        ])->concat($activities->map(fn ($log) => [
            'type' => 'activity',
            'action' => $log->activity,
            'old_status' => null,
            'new_status' => null,
            'notes' => null,
            'user_name' => $log->user?->name,
            'created_at' => $log->created_at,
        ]))->sortByDesc('created_at')->values();

        return response()->json(['history' => $history]);
    }
}
