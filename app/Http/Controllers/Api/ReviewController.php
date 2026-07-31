<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ReviewResource;
use App\Models\Project;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService,
    ) {}

    /**
     * Pending projects to review plus the reviewer's recent decisions.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->authorize('viewAny', Review::class);

        if ($user->hasRole('applicant')) {
            return response()->json([
                'reviews' => ReviewResource::collection(
                    Review::query()
                        ->with(['project:id,project_code,project_name,status', 'reviewer:id,name'])
                        ->whereHas('project', fn ($q) => $q->where('applicant_id', $user->id))
                        ->latest()
                        ->limit(50)
                        ->get(),
                ),
            ]);
        }

        $pending = Project::query()
            ->with(['applicant:id,name', 'documents:id,project_id,original_name,size,created_at'])
            ->withCount('documents')
            ->whereIn('status', ['submitted', 'revision', 'under_review'])
            ->latest('submitted_at')
            ->limit(100)
            ->get();

        $history = Review::query()
            ->with(['project:id,project_code,project_name,status', 'reviewer:id,name'])
            ->where('reviewer_id', $user->id)
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'pending' => ProjectResource::collection($pending),
            'reviews' => ReviewResource::collection($history),
        ]);
    }

    public function store(StoreReviewRequest $request): JsonResponse
    {
        $this->authorize('create', Review::class);

        $project = Project::query()->findOrFail($request->integer('project_id'));

        if (! $this->reviewService->isReviewable($project)) {
            throw ValidationException::withMessages([
                'project_id' => 'This project is not awaiting review.',
            ]);
        }

        $review = $this->reviewService->decide(
            $request->user(),
            $project,
            ProjectStatus::from($request->string('status')->toString()),
            $request->input('notes'),
        );

        return response()->json([
            'review' => new ReviewResource($review->load('reviewer:id,name', 'project:id,project_code,project_name,status')),
        ], 201);
    }
}
