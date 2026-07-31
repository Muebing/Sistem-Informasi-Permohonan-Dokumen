<script setup lang="ts">
import { FolderKanban, Plus, Search } from '@lucide/vue';
import { onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import api from '@/lib/api';
import { formatDate } from '@/lib/format';
import { useAuthStore } from '@/stores/auth';
import type {
    Paginated,
    Project,
    ProjectFilters,
    StatusValue,
} from '@/types/project';

const auth = useAuthStore();
const loading = ref(true);
const projects = ref<Paginated<Project> | null>(null);

const search = ref('');
const status = ref<StatusValue | ''>('');
const sort = ref<'newest' | 'oldest' | 'name' | 'status'>('newest');
const page = ref(1);

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(loadProjects, 300);
});

watch([status, sort, page], loadProjects);

async function loadProjects() {
    loading.value = true;

    try {
        const params: ProjectFilters = { page: page.value, sort: sort.value };

        if (search.value.trim()) {
            params.search = search.value.trim();
        }

        if (status.value) {
            params.status = status.value;
        }

        const { data } = await api.get<Paginated<Project>>('/projects', {
            params,
        });
        projects.value = data;
    } finally {
        loading.value = false;
    }
}

onMounted(loadProjects);
</script>

<template>
    <PageHeader
        title="Proyek"
        description="Kelola pengajuan proyek yang Anda buat."
    >
        <template #actions>
            <Button v-if="auth.isApplicant" as-child>
                <RouterLink :to="{ name: 'projects.create' }">
                    <Plus />
                    Proyek baru
                </RouterLink>
            </Button>
        </template>
    </PageHeader>

    <Card>
        <CardContent class="space-y-4 p-4">
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        class="pl-9"
                        placeholder="Cari kode, nama proyek, atau pemohon..."
                    />
                </div>
                <div class="flex gap-3">
                    <Select v-model="status">
                        <SelectTrigger class="w-full sm:w-44">
                            <SelectValue placeholder="Semua status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="draft">Draf</SelectItem>
                            <SelectItem value="submitted">Diajukan</SelectItem>
                            <SelectItem value="under_review"
                                >Ditinjau</SelectItem
                            >
                            <SelectItem value="revision">Revisi</SelectItem>
                            <SelectItem value="approved">Disetujui</SelectItem>
                            <SelectItem value="rejected">Ditolak</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="sort">
                        <SelectTrigger class="w-full sm:w-44">
                            <SelectValue placeholder="Urutkan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="newest">Terbaru</SelectItem>
                            <SelectItem value="oldest">Terlama</SelectItem>
                            <SelectItem value="name">Nama</SelectItem>
                            <SelectItem value="status">Status</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div v-if="loading" class="space-y-3">
                <Skeleton v-for="index in 5" :key="index" class="h-12" />
            </div>

            <div v-else-if="!projects?.data.length" class="pt-4">
                <EmptyState
                    :icon="FolderKanban"
                    title="Belum ada proyek"
                    description="Buat proyek pertama Anda untuk mulai mengajukan dokumen."
                />
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b text-left text-xs text-muted-foreground uppercase"
                        >
                            <th class="px-3 py-2 font-medium">Kode</th>
                            <th class="px-3 py-2 font-medium">Nama proyek</th>
                            <th class="px-3 py-2 font-medium">Status</th>
                            <th class="px-3 py-2 text-center font-medium">
                                Dokumen
                            </th>
                            <th class="px-3 py-2 font-medium">Dibuat</th>
                            <th class="px-3 py-2" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="project in projects.data"
                            :key="project.id"
                            class="border-b hover:bg-muted/50"
                        >
                            <td class="px-3 py-3 font-mono text-xs font-medium">
                                {{ project.project_code }}
                            </td>
                            <td class="px-3 py-3">
                                <p class="font-medium">
                                    {{ project.project_name }}
                                </p>
                                <p
                                    v-if="project.applicant"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ project.applicant.name }}
                                </p>
                            </td>
                            <td class="px-3 py-3">
                                <StatusBadge :status="project.status" />
                            </td>
                            <td class="px-3 py-3 text-center">
                                {{ project.documents_count ?? 0 }}
                            </td>
                            <td class="px-3 py-3 text-xs text-muted-foreground">
                                {{ formatDate(project.created_at) }}
                            </td>
                            <td class="px-3 py-3 text-right">
                                <Button variant="ghost" size="sm" as-child>
                                    <RouterLink
                                        :to="{
                                            name: 'projects.show',
                                            params: { id: project.id },
                                        }"
                                    >
                                        Detail
                                    </RouterLink>
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination
                v-if="projects"
                :meta="projects.meta"
                @change="page = $event"
            />
        </CardContent>
    </Card>
</template>
