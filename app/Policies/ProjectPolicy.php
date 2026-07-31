<?php

namespace App\Policies;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Any authenticated user may list projects (results are scoped by role).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * The owner and reviewers may view a project.
     */
    public function view(User $user, Project $project): bool
    {
        return $project->applicant_id === $user->id
            || $user->hasRole('reviewer');
    }

    /**
     * Only applicants may create projects.
     */
    public function create(User $user): bool
    {
        return $user->can('projects.create');
    }

    /**
     * Only the owner may edit, while the project is a draft or back with the
     * applicant in revision (per the workflow, revision lets the applicant
     * update the project and re-upload documents).
     */
    public function update(User $user, Project $project): bool
    {
        return $user->can('projects.update')
            && $project->applicant_id === $user->id
            && in_array($project->status, [ProjectStatus::Draft, ProjectStatus::Revision], true);
    }

    /**
     * Only the owner may delete, and only while the project is a draft.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->can('projects.delete')
            && $project->applicant_id === $user->id
            && $project->status === ProjectStatus::Draft;
    }

    /**
     * The owner may submit from draft or revision.
     */
    public function submit(User $user, Project $project): bool
    {
        return $user->can('projects.submit')
            && $project->applicant_id === $user->id
            && in_array($project->status, ProjectStatus::submittableStatuses(), true);
    }

    /**
     * The owner may upload documents while in draft or revision.
     */
    public function uploadDocument(User $user, Project $project): bool
    {
        return $user->can('documents.upload')
            && $project->applicant_id === $user->id
            && in_array($project->status, [ProjectStatus::Draft, ProjectStatus::Revision], true);
    }

    /**
     * The owner may delete documents while in draft or revision.
     */
    public function deleteDocument(User $user, Project $project): bool
    {
        return $this->uploadDocument($user, $project);
    }

    /**
     * Only reviewers may start a review, and only while the project
     * is freshly submitted (the revision loop returns to under review directly
     * on resubmission).
     */
    public function startReview(User $user, Project $project): bool
    {
        return $user->hasRole('reviewer')
            && $project->status === ProjectStatus::Submitted;
    }

    /**
     * Only reviewers may export project data.
     */
    public function export(User $user): bool
    {
        return $user->hasRole('reviewer');
    }
}
