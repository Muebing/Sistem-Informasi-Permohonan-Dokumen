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
     * Claim a freshly submitted project for review: submitted -> under_review.
     */
    public function startReview(User $reviewer, Project $project): Project
    {
        if ($project->status !== ProjectStatus::Submitted) {
            throw new InvalidArgumentException('Only submitted projects can be claimed for review.');
        }

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

        if ($project->status !== ProjectStatus::UnderReview) {
            throw new InvalidArgumentException('Project is not under review.');
        }

        return DB::transaction(function () use ($reviewer, $project, $decision, $notes): Review {
            $current = $project->status;

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

            if ($decision === ProjectStatus::Approved) {
                $this->activity->approval(
                    $reviewer,
                    $project,
                    'publish',
                    ProjectStatus::Approved,
                    ProjectStatus::Approved,
                    'Document ratified and issued.',
                );
            }

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
