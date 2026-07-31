export type Role = 'applicant' | 'reviewer' | 'admin';

export type User = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    roles: Role[];
    created_at: string;
    updated_at: string;
};

export type AuthResponse = {
    user: User;
    token: string;
};
