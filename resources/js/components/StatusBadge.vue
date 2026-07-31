<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { StatusPresent } from '@/types/project';

withDefaults(
    defineProps<{
        status: StatusPresent;
        dot?: boolean;
        size?: 'sm' | 'default';
    }>(),
    { dot: true, size: 'default' },
);
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center gap-1.5 rounded-full border border-transparent font-medium',
                status.color.badge,
                size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-xs',
            )
        "
    >
        <span
            v-if="dot"
            :class="cn('size-1.5 rounded-full', status.color.dot)"
        />
        {{ status.label }}
    </span>
</template>
