<script setup lang="ts">
import { ClipboardCheck, FolderKanban } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import api from '@/lib/api';
import { formatDateTime } from '@/lib/format';
import type { ReviewsResponse } from '@/types/dashboard';
import type { Project, Review } from '@/types/project';

const loading = ref(true);
const pending = ref<Project[]>([]);
const reviews = ref<Review[]>([]);
const tab = ref<'queue' | 'history'>('queue');

async function load() {
    loading.value = true;

    try {
        const { data } = await api.get<ReviewsResponse>('/reviews');
        pending.value = data.pending ?? [];
        reviews.value = data.reviews;
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

<template>
    <PageHeader
        title="Review"
        description="Antrian proyek yang menunggu review beserta riwayat keputusan Anda."
    />

    <div class="mb-4 flex w-fit items-center gap-1 rounded-lg border p-1">
        <Button
            variant="ghost"
            size="sm"
            :class="tab === 'queue' && 'bg-accent'"
            @click="tab = 'queue'"
        >
            Antrian ({{ pending.length }})
        </Button>
        <Button
            variant="ghost"
            size="sm"
            :class="tab === 'history' && 'bg-accent'"
            @click="tab = 'history'"
        >
            Riwayat
        </Button>
    </div>

    <Card>
        <CardContent class="p-4">
            <div v-if="loading" class="space-y-3">
                <Skeleton v-for="index in 4" :key="index" class="h-16" />
            </div>

            <template v-else-if="tab === 'queue'">
                <EmptyState
                    v-if="pending.length === 0"
                    :icon="ClipboardCheck"
                    title="Tidak ada proyek dalam antrian"
                    description="Proyek baru yang diajukan akan muncul di sini."
                />
                <ul v-else class="divide-y">
                    <li
                        v-for="project in pending"
                        :key="project.id"
                        class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge variant="outline" class="font-mono">
                                    {{ project.project_code }}
                                </Badge>
                                <StatusBadge
                                    :status="project.status"
                                    size="sm"
                                />
                            </div>
                            <p class="mt-1 truncate text-sm font-medium">
                                {{ project.project_name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ project.applicant?.name }} ·
                                {{ project.documents_count ?? 0 }} dokumen ·
                                diajukan
                                {{ formatDateTime(project.submitted_at) }}
                            </p>
                        </div>
                        <Button size="sm" as-child>
                            <RouterLink
                                :to="{
                                    name: 'projects.show',
                                    params: { id: project.id },
                                }"
                            >
                                <FolderKanban />
                                Buka proyek
                            </RouterLink>
                        </Button>
                    </li>
                </ul>
            </template>

            <template v-else>
                <EmptyState
                    v-if="reviews.length === 0"
                    :icon="ClipboardCheck"
                    title="Belum ada keputusan"
                    description="Keputusan review yang Anda buat akan muncul di sini."
                />
                <ul v-else class="divide-y">
                    <li v-for="review in reviews" :key="review.id" class="py-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <StatusBadge
                                :status="review.status"
                                size="sm"
                                :dot="false"
                            />
                            <span class="text-sm font-medium">{{
                                review.project?.project_name
                            }}</span>
                        </div>
                        <p
                            v-if="review.notes"
                            class="mt-1 text-sm text-muted-foreground"
                        >
                            {{ review.notes }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ formatDateTime(review.created_at) }}
                        </p>
                    </li>
                </ul>
            </template>
        </CardContent>
    </Card>
</template>
