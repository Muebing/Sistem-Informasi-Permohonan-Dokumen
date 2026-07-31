<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Mail\ReviewOutcomeNotification;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class ReviewService
{
    public function __construct(
        private readonly ActivityService $activity,
    ) {}

    /**
     * Claim a project for review: submitted/revision -> under_review.
     */
    public function startReview(User $reviewer, Project $project): Project
    {
        $previous = $project->status;

        $project->update(['status' => ProjectStatus::UnderReview]);

        $this->activity->approval(
            $reviewer,
            $project,
            'review',
            $previous,
            ProjectStatus::UnderReview,
            'Review started.',
        );

        return $project;
    }

    /**
     * Record a reviewer decision and advance the workflow.
     */
    public function decide(User $reviewer, Project $project, ProjectStatus $decision, ?string $notes): Review
    {
        if (! in_array($decision, [ProjectStatus::Approved, ProjectStatus::Rejected, ProjectStatus::Revision], true)) {
            throw new InvalidArgumentException('Invalid review decision.');
        }

        return DB::transaction(function () use ($reviewer, $project, $decision, $notes): Review {
            $current = $project->status;

            if ($current === ProjectStatus::Submitted || $current === ProjectStatus::Revision) {
                $project->update(['status' => ProjectStatus::UnderReview]);

                $this->activity->approval(
                    $reviewer,
                    $project,
                    'review',
                    $current,
                    ProjectStatus::UnderReview,
                    'Review started.',
                );

                $current = ProjectStatus::UnderReview;
            }

            $project->update(['status' => $decision]);

            $review = Review::create([
                'project_id' => $project->id,
                'reviewer_id' => $reviewer->id,
                'status' => $decision,
                'notes' => $notes,
            ]);

            $action = match ($decision) {
                ProjectStatus::Approved => 'approve',
                ProjectStatus::Rejected => 'reject',
                ProjectStatus::Revision => 'request_revision',
            };

            $this->activity->approval(
                $reviewer,
                $project,
                $action,
                $current,
                $decision,
                $notes,
            );

            Mail::to($project->applicant)
                ->queue(new ReviewOutcomeNotification($project, $decision, $notes));

            return $review;
        });
    }

    /**
     * Whether a project can be reviewed (has been submitted at least once).
     */
    public function isReviewable(Project $project): bool
    {
        return in_array(
            $project->status,
            ProjectStatus::pendingReviewStatuses(),
            true,
        );
    }
}
