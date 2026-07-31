export type StatusValue =
    | 'draft'
    | 'submitted'
    | 'under_review'
    | 'revision'
    | 'approved'
    | 'rejected';

export type StatusPresent = {
    value: StatusValue;
    label: string;
    color: {
        badge: string;
        dot: string;
    };
};

export type UserSummary = {
    id: number;
    name: string;
    email: string;
};

export type ProjectDocument = {
    id: number;
    original_name: string;
    stored_name: string;
    path: string;
    mime_type: string;
    size: number;
    size_human: string;
    hash: string;
    processed_at: string | null;
    created_at: string;
    download_url: string;
};

export type Project = {
    id: number;
    project_code: string;
    project_name: string;
    description: string | null;
    status: StatusPresent;
    applicant?: UserSummary;
    documents_count?: number;
    submitted_at: string | null;
    created_at: string;
    updated_at: string;
};

export type Review = {
    id: number;
    status: StatusPresent;
    notes: string | null;
    reviewer?: { id: number; name: string };
    project?: {
        id: number;
        project_code: string;
        project_name: string;
        status: StatusPresent;
    };
    created_at: string;
};

export type ProjectDetail = Project & {
    documents: ProjectDocument[];
    reviews: Review[];
};

export type HistoryEntry = {
    type: 'approval' | 'activity';
    action: string;
    old_status: string | null;
    new_status: string | null;
    notes: string | null;
    user_name: string | null;
    created_at: string;
};

export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};

export type ProjectFilters = {
    search?: string;
    status?: StatusValue | '';
    sort?: 'newest' | 'oldest' | 'name' | 'status';
    page?: number;
};
