<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router';
import Logo from '@/components/Logo.vue';
import SidebarNav from '@/components/SidebarNav.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    Sidebar,
    SidebarFooter,
    SidebarHeader,
    SidebarInset,
    SidebarProvider,
    SidebarRail,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import UserMenu from '@/components/UserMenu.vue';
import { getInitials } from '@/composables/useInitials';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
</script>

<template>
    <SidebarProvider>
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader class="py-3">
                <RouterLink to="/" class="flex items-center gap-2 px-2">
                    <Logo />
                </RouterLink>
            </SidebarHeader>

            <SidebarNav />

            <SidebarFooter>
                <div class="flex items-center gap-2 px-1">
                    <Avatar class="size-8 shrink-0">
                        <AvatarFallback
                            class="bg-primary text-xs text-primary-foreground"
                        >
                            {{ getInitials(auth.user?.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ auth.user?.name }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ auth.user?.email }}
                        </p>
                    </div>
                </div>
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>

        <SidebarInset>
            <header
                class="sticky top-0 z-10 flex h-14 shrink-0 items-center justify-between border-b bg-background/95 px-4 backdrop-blur supports-[backdrop-filter]:bg-background/60"
            >
                <div class="flex items-center gap-2">
                    <SidebarTrigger class="-ml-1" />
                </div>
                <div class="flex items-center gap-1">
                    <ThemeToggle />
                    <UserMenu />
                </div>
            </header>
            <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 lg:px-6">
                <RouterView />
            </main>
        </SidebarInset>
    </SidebarProvider>
</template>
