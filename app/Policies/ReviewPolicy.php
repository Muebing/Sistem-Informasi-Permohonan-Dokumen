<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Only reviewers and admins may create reviews.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['reviewer', 'admin']);
    }

    /**
     * Reviewers and admins may list reviews; applicants may only see reviews
     * on their own projects (scoped in the controller/repository).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Reviewers/admins see everything; owners see reviews on their projects.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->hasAnyRole(['reviewer', 'admin'])
            || $project->applicant_id === $user->id;
    }
}
