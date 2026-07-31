<script setup lang="ts">
import { LogOut, UserRound } from '@lucide/vue';
import { useRouter } from 'vue-router';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { getInitials } from '@/composables/useInitials';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();

async function handleLogout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon-sm"
                class="rounded-full"
                aria-label="Menu akun"
            >
                <Avatar class="size-7">
                    <AvatarFallback
                        class="bg-primary text-xs text-primary-foreground"
                    >
                        {{ getInitials(auth.user?.name) }}
                    </AvatarFallback>
                </Avatar>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuLabel class="flex flex-col gap-0.5">
                <span class="font-semibold">{{ auth.user?.name }}</span>
                <span class="text-xs font-normal text-muted-foreground">{{
                    auth.user?.email
                }}</span>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                @select="router.push({ name: 'settings.profile' })"
            >
                <UserRound />
                Profil
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem variant="destructive" @select="handleLogout">
                <LogOut />
                Keluar
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
