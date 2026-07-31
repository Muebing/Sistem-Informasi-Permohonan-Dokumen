<script setup lang="ts">
import { ClipboardCheck } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import api from '@/lib/api';
import { formatDateTime } from '@/lib/format';
import type { ReviewsResponse } from '@/types/dashboard';
import type { Review } from '@/types/project';

const loading = ref(true);
const reviews = ref<Review[]>([]);

async function load() {
    loading.value = true;

    try {
        const { data } = await api.get<ReviewsResponse>('/reviews');
        reviews.value = data.reviews;
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

<template>
    <PageHeader
        title="Riwayat"
        description="Hasil review untuk seluruh proyek yang pernah Anda ajukan."
    />

    <Card>
        <CardContent class="p-4">
            <div v-if="loading" class="space-y-3">
                <Skeleton v-for="index in 4" :key="index" class="h-16" />
            </div>

            <EmptyState
                v-else-if="reviews.length === 0"
                :icon="ClipboardCheck"
                title="Belum ada review"
                description="Hasil review proyek Anda akan muncul di sini."
            />

            <ul v-else class="divide-y">
                <li v-for="review in reviews" :key="review.id" class="py-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <StatusBadge
                            :status="review.status"
                            size="sm"
                            :dot="false"
                        />
                        <RouterLink
                            v-if="review.project"
                            :to="{
                                name: 'projects.show',
                                params: { id: review.project.id },
                            }"
                            class="text-sm font-medium hover:text-primary"
                        >
                            {{ review.project.project_code }} —
                            {{ review.project.project_name }}
                        </RouterLink>
                    </div>
                    <p
                        v-if="review.notes"
                        class="mt-1 text-sm text-muted-foreground"
                    >
                        {{ review.notes }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ review.reviewer?.name ?? 'Reviewer' }} ·
                        {{ formatDateTime(review.created_at) }}
                    </p>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
