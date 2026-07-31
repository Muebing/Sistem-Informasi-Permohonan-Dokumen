<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    private const TTL = 300;

    /**
     * Summary for an applicant: counts, monthly submissions, recent activity.
     *
     * @return array<string, mixed>
     */
    public function forApplicant(User $user): array
    {
        return Cache::remember(
            "siperdo:dashboard:applicant:{$user->id}",
            self::TTL,
            fn (): array => $this->buildApplicantDashboard($user),
        );
    }

    /**
     * Summary for reviewers: global counts, monthly reviews, recent reviews.
     *
     * @return array<string, mixed>
     */
    public function forReviewer(): array
    {
        return Cache::remember(
            'siperdo:dashboard:reviewer',
            self::TTL,
            fn (): array => $this->buildReviewerDashboard(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildApplicantDashboard(User $user): array
    {
        $since = CarbonImmutable::now()->subMonths(11)->startOfMonth();

        $summary = Project::query()
            ->selectRaw('status, count(*) as total')
            ->where('applicant_id', $user->id)
            ->groupBy('status')
            ->pluck('total', 'status');

        $monthly = Project::query()
            ->where('applicant_id', $user->id)
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', $since)
            ->selectRaw("to_char(submitted_at, 'YYYY-MM') as month, count(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $recent = ActivityLog::query()
            ->with('project:id,project_code,project_name,status')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'activity' => $log->activity,
                'created_at' => $log->created_at->toIso8601String(),
                'project' => $log->project ? [
                    'id' => $log->project->id,
                    'project_code' => $log->project->project_code,
                    'project_name' => $log->project->project_name,
                    'status' => $log->project->status->value,
                ] : null,
            ])
            ->all();

        return [
            'summary' => $this->emptySummary()->merge($summary)->all(),
            'monthly' => $this->fillMonthlySeries($monthly),
            'recent_activity' => $recent,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReviewerDashboard(): array
    {
        $since = CarbonImmutable::now()->subMonths(11)->startOfMonth();

        $summary = Project::query()
            ->selectRaw('status, count(*) as total')
            ->whereIn('status', [
                ProjectStatus::Submitted->value,
                ProjectStatus::UnderReview->value,
                ProjectStatus::Approved->value,
                ProjectStatus::Rejected->value,
                ProjectStatus::Revision->value,
            ])
            ->groupBy('status')
            ->pluck('total', 'status');

        $monthly = Review::query()
            ->where('created_at', '>=', $since)
            ->selectRaw("to_char(created_at, 'YYYY-MM') as month, count(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $recent = Review::query()
            ->with([
                'project:id,project_code,project_name,status',
                'reviewer:id,name',
            ])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Review $review) => [
                'status' => $review->status->value,
                'notes' => $review->notes,
                'created_at' => $review->created_at->toIso8601String(),
                'reviewer' => [
                    'id' => $review->reviewer?->id,
                    'name' => $review->reviewer?->name,
                ],
                'project' => $review->project ? [
                    'id' => $review->project->id,
                    'project_code' => $review->project->project_code,
                    'project_name' => $review->project->project_name,
                    'status' => $review->project->status->value,
                ] : null,
            ])
            ->all();

        return [
            'summary' => $this->emptySummary()->merge($summary)->all(),
            'monthly' => $this->fillMonthlySeries($monthly),
            'recent_reviews' => $recent,
        ];
    }

    /**
     * @return Collection<string, int>
     */
    private function emptySummary(): Collection
    {
        $keys = [
            'draft', 'submitted', 'under_review', 'revision', 'approved', 'rejected',
        ];

        return collect($keys)->mapWithKeys(fn (string $key) => [$key => 0]);
    }

    /**
     * Ensure all 12 trailing months exist in the series.
     *
     * @param  Collection<string, int>  $counts
     * @return array<int, array{month: string, total: int}>
     */
    private function fillMonthlySeries(Collection $counts): array
    {
        $series = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = CarbonImmutable::now()->startOfMonth()->subMonths($i)->format('Y-m');

            $series[] = [
                'month' => $month,
                'total' => (int) ($counts[$month] ?? 0),
            ];
        }

        return $series;
    }
}
