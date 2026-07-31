import { createRouter, createWebHashHistory } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { TOKEN_KEY } from '@/lib/api';
import { useAuthStore } from '@/stores/auth';
import type { Role } from '@/types/auth';

const router = createRouter({
    history: createWebHashHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/',
            component: AuthLayout,
            meta: { guest: true },
            children: [
                {
                    path: 'login',
                    name: 'login',
                    component: () => import('@/pages/auth/Login.vue'),
                },
                {
                    path: 'register',
                    name: 'register',
                    component: () => import('@/pages/auth/Register.vue'),
                },
                {
                    path: 'forgot-password',
                    name: 'forgot-password',
                    component: () => import('@/pages/auth/ForgotPassword.vue'),
                },
                {
                    path: 'reset-password',
                    name: 'reset-password',
                    component: () => import('@/pages/auth/ResetPassword.vue'),
                },
            ],
        },
        {
            path: '/',
            component: AppLayout,
            meta: { requiresAuth: true },
            children: [
                {
                    path: '',
                    name: 'dashboard',
                    component: () => import('@/pages/Dashboard.vue'),
                },
                {
                    path: 'projects',
                    name: 'projects.index',
                    component: () => import('@/pages/projects/Index.vue'),
                },
                {
                    path: 'projects/create',
                    name: 'projects.create',
                    component: () => import('@/pages/projects/Create.vue'),
                    meta: { roles: ['applicant'] },
                },
                {
                    path: 'projects/:id',
                    name: 'projects.show',
                    component: () => import('@/pages/projects/Show.vue'),
                },
                {
                    path: 'reviews',
                    name: 'reviews.index',
                    component: () => import('@/pages/reviews/Index.vue'),
                    meta: { roles: ['reviewer'] },
                },
                {
                    path: 'history',
                    name: 'history.index',
                    component: () => import('@/pages/history/Index.vue'),
                },
                {
                    path: 'settings/profile',
                    name: 'settings.profile',
                    component: () => import('@/pages/settings/Profile.vue'),
                },
            ],
        },
        {
            path: '/:pathMatch(.*)*',
            redirect: '/',
        },
    ],
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (!auth.isAuthenticated && localStorage.getItem(TOKEN_KEY)) {
        try {
            await auth.fetchMe();
        } catch {
            auth.clear();
        }
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }

    if (to.meta.roles && auth.isAuthenticated) {
        const allowed = (to.meta.roles as Role[]).some((role) =>
            auth.roles.includes(role),
        );

        if (!allowed) {
            return { name: 'dashboard' };
        }
    }

    return true;
});

export default router;
