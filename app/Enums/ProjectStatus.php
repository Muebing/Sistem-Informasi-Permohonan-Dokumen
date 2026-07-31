<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Revision = 'revision';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::Revision => 'Revision',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * Tailwind-friendly color scheme used by the frontend.
     *
     * @return array{badge: string, dot: string}
     */
    public function color(): array
    {
        return match ($this) {
            self::Draft => ['badge' => 'bg-slate-100 text-slate-700', 'dot' => 'bg-slate-500'],
            self::Submitted => ['badge' => 'bg-blue-50 text-blue-700', 'dot' => 'bg-blue-500'],
            self::UnderReview => ['badge' => 'bg-indigo-50 text-indigo-700', 'dot' => 'bg-indigo-500'],
            self::Revision => ['badge' => 'bg-amber-50 text-amber-700', 'dot' => 'bg-amber-500'],
            self::Approved => ['badge' => 'bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'],
            self::Rejected => ['badge' => 'bg-rose-50 text-rose-700', 'dot' => 'bg-rose-500'],
        };
    }

    /**
     * Statuses a project may be in when the applicant may submit it.
     *
     * @return array<int, ProjectStatus>
     */
    public static function submittableStatuses(): array
    {
        return [self::Draft, self::Revision];
    }

    /**
     * Statuses in which a reviewer may record a decision.
     *
     * Per the business process a project must be actively under review;
     * while in "submitted" the reviewer must first start the review, and
     * while in "revision" the project is back with the applicant.
     *
     * @return array<int, ProjectStatus>
     */
    public static function pendingReviewStatuses(): array
    {
        return [self::UnderReview];
    }
}
