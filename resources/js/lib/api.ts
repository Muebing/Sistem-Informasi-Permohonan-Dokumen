import axios from 'axios';

export const TOKEN_KEY = 'siperdo_token';

const api = axios.create({
    baseURL: '/api/v1',
    headers: {
        Accept: 'application/json',
    },
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem(TOKEN_KEY);

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            const url: string = error.config?.url ?? '';

            if (!url.startsWith('/auth/')) {
                localStorage.removeItem(TOKEN_KEY);
                window.location.assign('#/login');
            }
        }

        return Promise.reject(error);
    },
);

export type ApiValidationError = {
    message?: string;
    errors?: Record<string, string[]>;
};

export function getErrorMessage(error: unknown): string {
    if (axios.isAxiosError<ApiValidationError>(error)) {
        const errors = error.response?.data?.errors;

        if (errors) {
            const first = Object.values(errors)[0];

            if (first?.length) {
                return first[0];
            }
        }

        if (error.response?.data?.message) {
            return error.response.data.message;
        }

        if (error.message) {
            return error.message;
        }
    }

    return 'Terjadi kesalahan. Silakan coba lagi.';
}

export default api;
