<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\ActivityLog;
use App\Models\ApprovalLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Central place for audit logging and dashboard cache invalidation.
 */
class ActivityService
{
    /**
     * Write a generic activity log entry.
     */
    public function log(?User $user, string $activity, ?Project $project = null): void
    {
        ActivityLog::create([
            'user_id' => $user?->id,
            'project_id' => $project?->id,
            'activity' => $activity,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 190),
        ]);

        $this->invalidateDashboardCaches($project?->applicant_id);
    }

    /**
     * Write an approval workflow transition (approval log + activity log).
     */
    public function approval(
        ?User $user,
        Project $project,
        string $action,
        ?ProjectStatus $oldStatus,
        ?ProjectStatus $newStatus,
        ?string $notes = null,
    ): void {
        ApprovalLog::create([
            'project_id' => $project->id,
            'user_id' => $user?->id,
            'action' => $action,
            'old_status' => $oldStatus?->value,
            'new_status' => $newStatus?->value,
            'notes' => $notes,
        ]);

        $this->log($user, $action, $project);
    }

    /**
     * Invalidate cached dashboard summaries affected by a change.
     */
    public function invalidateDashboardCaches(?int $applicantId = null, ?int $actorId = null): void
    {
        Cache::forget('siperdo:dashboard:reviewer');

        foreach (array_unique(array_filter([$applicantId, $actorId])) as $id) {
            Cache::forget("siperdo:dashboard:applicant:{$id}");
        }
    }
}
