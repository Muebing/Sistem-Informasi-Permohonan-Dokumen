<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Mail\ProjectSubmittedNotification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class ProjectService
{
    public function __construct(
        private readonly ActivityService $activity,
    ) {}

    /**
     * Create a new draft project owned by the given applicant.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $applicant, array $data): Project
    {
        $project = Project::create([
            'project_code' => $this->generateCode(),
            'project_name' => $data['project_name'],
            'description' => $data['description'] ?? null,
            'applicant_id' => $applicant->id,
            'status' => ProjectStatus::Draft,
        ]);

        $this->activity->log($applicant, 'created project', $project);

        return $project;
    }

    /**
     * Update project details (only allowed while in draft).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, Project $project, array $data): Project
    {
        $project->update([
            'project_name' => $data['project_name'],
            'description' => $data['description'] ?? null,
        ]);

        $this->activity->log($user, 'updated project', $project);

        return $project;
    }

    /**
     * Delete a project and its stored documents.
     */
    public function delete(User $user, Project $project): void
    {
        Storage::disk('documents')->deleteDirectory((string) $project->id);

        $project->delete();

        $this->activity->log($user, 'deleted project');
    }

    /**
     * Submit a project for review.
     *
     * A first submission moves draft -> submitted; resubmitting after a
     * revision moves revision -> under_review so the project re-enters the
     * review process directly (per the business process diagram).
     */
    public function submit(User $user, Project $project): Project
    {
        $previous = $project->status;

        if (! in_array($previous, ProjectStatus::submittableStatuses(), true)) {
            throw new InvalidArgumentException('Project is not in a submittable status.');
        }

        $resubmission = $previous === ProjectStatus::Revision;

        $newStatus = $resubmission
            ? ProjectStatus::UnderReview
            : ProjectStatus::Submitted;

        $project->update([
            'status' => $newStatus,
            'submitted_at' => now(),
        ]);

        $this->activity->approval(
            $user,
            $project,
            $resubmission ? 'resubmit' : 'submit',
            $previous,
            $newStatus,
            $resubmission
                ? 'Revised project resubmitted for review.'
                : 'Project submitted for review.',
        );

        $this->notifyReviewers($project);

        return $project;
    }

    /**
     * Notify reviewers that a submission awaits review.
     */
    private function notifyReviewers(Project $project): void
    {
        $recipients = User::query()
            ->role('reviewer')
            ->pluck('email');

        if ($recipients->isNotEmpty()) {
            Mail::to($recipients->all())
                ->queue(new ProjectSubmittedNotification($project));
        }
    }

    /**
     * Generate a unique sequential project code, e.g. SPRD-2026-00001.
     */
    public function generateCode(): string
    {
        $year = now()->year;

        return DB::transaction(function () use ($year): string {
            $last = Project::whereYear('created_at', $year)
                ->where('project_code', 'like', "SPRD-{$year}-%")
                ->orderByDesc('project_code')
                ->value('project_code');

            $sequence = $last ? ((int) substr($last, -5)) + 1 : 1;

            $code = sprintf('SPRD-%d-%05d', $year, $sequence);

            if (Project::where('project_code', $code)->exists()) {
                throw new RuntimeException('Unable to generate a unique project code.');
            }

            return $code;
        });
    }
}
