<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { Inbox } from '@lucide/vue';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        icon?: LucideIcon;
        action?: string;
    }>(),
    { icon: Inbox, description: '', action: '' },
);
</script>

<template>
    <div
        class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-card px-6 py-12 text-center text-muted-foreground"
    >
        <div
            class="mb-4 flex size-12 items-center justify-center rounded-full bg-muted"
        >
            <component :is="icon" class="size-6" />
        </div>
        <h3 class="text-sm font-semibold text-foreground">{{ title }}</h3>
        <p v-if="description" class="mt-1 max-w-sm text-sm">
            {{ description }}
        </p>
    </div>
</template>
