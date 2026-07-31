<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types/project';

const props = defineProps<{
    meta: Paginated<unknown>['meta'];
}>();

const emit = defineEmits<{
    (e: 'change', page: number): void;
}>();

const pages = computed<number[]>(() => {
    const total = props.meta.last_page;
    const current = props.meta.current_page;

    if (total <= 7) {
        return Array.from({ length: total }, (_, index) => index + 1);
    }

    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);
    const result = [1];

    if (start > 2) {
        result.push(-1);
    }

    for (let page = start; page <= end; page += 1) {
        result.push(page);
    }

    if (end < total - 1) {
        result.push(-2);
    }

    result.push(total);

    return result;
});

function goTo(page: number) {
    if (
        page >= 1 &&
        page <= props.meta.last_page &&
        page !== props.meta.current_page
    ) {
        emit('change', page);
    }
}
</script>

<template>
    <nav
        v-if="meta.last_page > 1"
        class="flex items-center justify-between gap-4"
    >
        <p class="text-sm text-muted-foreground">
            Menampilkan {{ meta.from ?? 0 }}-{{ meta.to ?? 0 }} dari
            {{ meta.total }}
        </p>
        <div class="flex items-center gap-1">
            <Button
                variant="outline"
                size="sm"
                :disabled="meta.current_page <= 1"
                @click="goTo(meta.current_page - 1)"
            >
                <ChevronLeft />
                <span class="sr-only">Halaman sebelumnya</span>
            </Button>
            <template v-for="page in pages" :key="page">
                <Button
                    v-if="page > 0"
                    variant="outline"
                    size="icon-sm"
                    :class="
                        page === meta.current_page &&
                        'border-primary bg-primary text-primary-foreground'
                    "
                    @click="goTo(page)"
                >
                    {{ page }}
                </Button>
                <span v-else class="px-1 text-muted-foreground">…</span>
            </template>
            <Button
                variant="outline"
                size="sm"
                :disabled="meta.current_page >= meta.last_page"
                @click="goTo(meta.current_page + 1)"
            >
                <ChevronRight />
                <span class="sr-only">Halaman berikutnya</span>
            </Button>
        </div>
    </nav>
</template>
