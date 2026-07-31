<?php

namespace App\Repositories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectRepository
{
    /**
     * Paginate projects with search, filter and sort support.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Project>
     */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Project>
     */
    public function forApplicant(array $filters, int $applicantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginate([...$filters, 'applicant_id' => $applicantId], $perPage);
    }

    /**
     * Build a query with search, filter and sort support.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Project>
     */
    public function query(array $filters): Builder
    {
        $query = Project::query()
            ->with(['applicant:id,name,email'])
            ->withCount('documents');

        $this->applySearch($query, $filters['search'] ?? null);
        $this->applyStatusFilter($query, $filters['status'] ?? null);
        $this->applyApplicantFilter($query, $filters['applicant_id'] ?? null);
        $this->applyReviewerFilter($query, $filters['reviewer_id'] ?? null);
        $this->applyDateRange($query, $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        $this->applySort($query, $filters['sort'] ?? 'newest');

        return $query;
    }

    /**
     * @param  Builder<Project>  $query
     */
    private function applySearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function (Builder $q) use ($search): void {
            $q->where('project_code', 'ilike', "%{$search}%")
                ->orWhere('project_name', 'ilike', "%{$search}%")
                ->orWhereHas('applicant', function (Builder $a) use ($search): void {
                    $a->where('name', 'ilike', "%{$search}%");
                });
        });
    }

    /**
     * @param  Builder<Project>  $query
     */
    private function applyStatusFilter(Builder $query, ?string $status): void
    {
        if ($status && ProjectStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        }
    }

    /**
     * @param  Builder<Project>  $query
     */
    private function applyApplicantFilter(Builder $query, int|string|null $applicantId): void
    {
        if ($applicantId) {
            $query->where('applicant_id', (int) $applicantId);
        }
    }

    /**
     * @param  Builder<Project>  $query
     */
    private function applyReviewerFilter(Builder $query, int|string|null $reviewerId): void
    {
        if ($reviewerId) {
            $query->whereHas('reviews', function (Builder $r) use ($reviewerId): void {
                $r->where('reviewer_id', (int) $reviewerId);
            });
        }
    }

    /**
     * @param  Builder<Project>  $query
     */
    private function applyDateRange(Builder $query, ?string $from, ?string $to): void
    {
        if ($from) {
            $query->whereDate('submitted_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('submitted_at', '<=', $to);
        }
    }

    /**
     * @param  Builder<Project>  $query
     */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->orderBy('created_at'),
            'name' => $query->orderBy('project_name'),
            'status' => $query->orderBy('status')->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };
    }
}
