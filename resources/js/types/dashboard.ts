import type { Project, Review } from './project';

export type DashboardSummary = Record<string, number>;

export type MonthlySeries = {
    month: string;
    total: number;
};

export type RecentActivity = {
    activity: string;
    created_at: string;
    project: {
        id: number;
        project_code: string;
        project_name: string;
        status: string;
    } | null;
};

export type ReviewerRecentReview = {
    status: string;
    notes: string | null;
    created_at: string;
    reviewer: {
        id: number | null;
        name: string | null;
    };
    project: {
        id: number;
        project_code: string;
        project_name: string;
        status: string;
    } | null;
};

export type ApplicantDashboard = {
    summary: DashboardSummary;
    monthly: MonthlySeries[];
    recent_activity: RecentActivity[];
};

export type ReviewerDashboard = {
    summary: DashboardSummary;
    monthly: MonthlySeries[];
    recent_reviews: ReviewerRecentReview[];
};

export type ReviewsResponse = {
    pending?: Project[];
    reviews: Review[];
};
