<script setup lang="ts">
import {
    ClipboardCheck,
    FolderKanban,
    History,
    LayoutDashboard,
    Settings,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
    SidebarContent,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useAuthStore } from '@/stores/auth';
import type { Role } from '@/types/auth';

const auth = useAuthStore();
const route = useRoute();

type NavLink = {
    title: string;
    href: string;
    name: string;
    icon: LucideIcon;
    roles?: Role[];
};

const links: NavLink[] = [
    {
        title: 'Dashboard',
        href: '/',
        name: 'dashboard',
        icon: LayoutDashboard,
    },
    {
        title: 'Proyek',
        href: '/projects',
        name: 'projects.index',
        icon: FolderKanban,
        roles: ['applicant', 'admin'],
    },
    {
        title: 'Review',
        href: '/reviews',
        name: 'reviews.index',
        icon: ClipboardCheck,
        roles: ['reviewer', 'admin'],
    },
    {
        title: 'Riwayat',
        href: '/history',
        name: 'history.index',
        icon: History,
        roles: ['applicant'],
    },
    {
        title: 'Pengaturan',
        href: '/settings/profile',
        name: 'settings.profile',
        icon: Settings,
    },
];

const visibleLinks = computed(() =>
    links.filter(
        (link) =>
            !link.roles || link.roles.some((role) => auth.roles.includes(role)),
    ),
);

function isActive(link: NavLink): boolean {
    if (link.name === 'dashboard') {
        return route.name === 'dashboard';
    }

    if (link.href === '/projects') {
        return route.name?.toString().startsWith('projects') ?? false;
    }

    return route.name === link.name;
}
</script>

<template>
    <SidebarContent>
        <SidebarGroup>
            <SidebarGroupLabel>Aplikasi</SidebarGroupLabel>
            <SidebarGroupContent>
                <SidebarMenu>
                    <SidebarMenuItem
                        v-for="link in visibleLinks"
                        :key="link.name"
                    >
                        <SidebarMenuButton
                            as-child
                            :is-active="isActive(link)"
                            :tooltip="link.title"
                        >
                            <RouterLink :to="link.href">
                                <component :is="link.icon" />
                                <span>{{ link.title }}</span>
                            </RouterLink>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    </SidebarContent>
</template>
