import { defineStore } from 'pinia';
import api, { TOKEN_KEY } from '@/lib/api';
import type { AuthResponse, User } from '@/types/auth';

type RegisterPayload = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null as User | null,
        loading: false,
    }),
    getters: {
        isAuthenticated: (state) => state.user !== null,
        roles: (state) => state.user?.roles ?? [],
        isApplicant: (state) =>
            state.user?.roles.includes('applicant') ?? false,
        isReviewer: (state) => state.user?.roles.includes('reviewer') ?? false,
        isAdmin: (state) => state.user?.roles.includes('admin') ?? false,
    },
    actions: {
        async fetchMe(): Promise<User> {
            const { data } = await api.get<{ user: User }>('/auth/me');
            this.user = data.user;

            return data.user;
        },

        async login(email: string, password: string): Promise<void> {
            const { data } = await api.post<AuthResponse>('/auth/login', {
                email,
                password,
            });
            this.user = data.user;
            localStorage.setItem(TOKEN_KEY, data.token);
        },

        async register(payload: RegisterPayload): Promise<void> {
            const { data } = await api.post<AuthResponse>(
                '/auth/register',
                payload,
            );
            this.user = data.user;
            localStorage.setItem(TOKEN_KEY, data.token);
        },

        async logout(): Promise<void> {
            try {
                await api.post('/auth/logout');
            } catch {
                // Ignore network errors while logging out.
            } finally {
                this.clear();
            }
        },

        clear(): void {
            this.user = null;
            localStorage.removeItem(TOKEN_KEY);
        },
    },
});
