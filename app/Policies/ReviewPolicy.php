<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Only reviewers may create reviews.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('reviewer');
    }

    /**
     * Reviewers may list reviews; applicants may only see reviews
     * on their own projects (scoped in the controller/repository).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Reviewers see everything; owners see reviews on their projects.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->hasRole('reviewer')
            || $project->applicant_id === $user->id;
    }
}
