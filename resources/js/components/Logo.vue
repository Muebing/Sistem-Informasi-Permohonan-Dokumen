<script setup lang="ts">
defineProps<{
    compact?: boolean;
}>();
</script>

<template>
    <div class="flex items-center gap-2">
        <div
            class="flex size-9 items-center justify-center rounded-lg bg-primary text-primary-foreground"
        >
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="size-5"
            >
                <path
                    d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"
                />
                <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                <path d="M8 13h8" />
                <path d="M8 17h5" />
            </svg>
        </div>
        <span v-if="!compact" class="text-lg font-semibold tracking-tight"
            >SIPERDO</span
        >
    </div>
</template>
