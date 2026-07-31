<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router';
import Logo from '@/components/Logo.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <header class="flex h-14 items-center justify-between px-4">
            <RouterLink to="/" class="flex items-center gap-2">
                <Logo />
            </RouterLink>
            <ThemeToggle />
        </header>

        <main class="flex flex-1 items-center justify-center px-4 pb-12">
            <div class="w-full max-w-md">
                <RouterView />
            </div>
        </main>

        <footer class="pb-6 text-center text-xs text-muted-foreground">
            © 2026 SIPERDO. Semua hak dilindungi.
        </footer>
    </div>
</template>
