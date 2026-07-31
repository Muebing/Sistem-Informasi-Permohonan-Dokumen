<script setup lang="ts">
import type { ApexOptions } from 'apexcharts';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import VueApexCharts from 'vue3-apexcharts';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import api from '@/lib/api';
import { formatDateTime } from '@/lib/format';
import { useAuthStore } from '@/stores/auth';
import type {
    ApplicantDashboard,
    MonthlySeries,
    ReviewerDashboard,
} from '@/types/dashboard';
import type { StatusPresent } from '@/types/project';

const auth = useAuthStore();
const loading = ref(true);
const applicant = ref<ApplicantDashboard | null>(null);
const reviewer = ref<ReviewerDashboard | null>(null);

const isApplicant = computed(() => auth.isApplicant);

const statusLabels: Record<string, StatusPresent> = {
    draft: {
        value: 'draft',
        label: 'Draf',
        color: { badge: 'bg-slate-100 text-slate-700', dot: 'bg-slate-500' },
    },
    submitted: {
        value: 'submitted',
        label: 'Diajukan',
        color: { badge: 'bg-blue-50 text-blue-700', dot: 'bg-blue-500' },
    },
    under_review: {
        value: 'under_review',
        label: 'Ditinjau',
        color: { badge: 'bg-indigo-50 text-indigo-700', dot: 'bg-indigo-500' },
    },
    revision: {
        value: 'revision',
        label: 'Revisi',
        color: { badge: 'bg-amber-50 text-amber-700', dot: 'bg-amber-500' },
    },
    approved: {
        value: 'approved',
        label: 'Disetujui',
        color: {
            badge: 'bg-emerald-50 text-emerald-700',
            dot: 'bg-emerald-500',
        },
    },
    rejected: {
        value: 'rejected',
        label: 'Ditolak',
        color: { badge: 'bg-rose-50 text-rose-700', dot: 'bg-rose-500' },
    },
};

type RecentItem = {
    key: string;
    title: string;
    subtitle?: string;
    projectId?: number;
    projectLabel?: string;
    createdAt: string;
};

const summary = computed<Record<string, number>>(
    () =>
        (isApplicant.value
            ? applicant.value?.summary
            : reviewer.value?.summary) ?? {},
);

const monthly = computed<MonthlySeries[]>(
    () =>
        (isApplicant.value
            ? applicant.value?.monthly
            : reviewer.value?.monthly) ?? [],
);

const recentItems = computed<RecentItem[]>(() => {
    if (isApplicant.value) {
        return (applicant.value?.recent_activity ?? []).map((entry) => ({
            key: `activity-${entry.created_at}`,
            title: entry.activity,
            projectId: entry.project?.id,
            projectLabel: entry.project
                ? `${entry.project.project_code} — ${entry.project.project_name}`
                : undefined,
            createdAt: entry.created_at,
        }));
    }

    return (reviewer.value?.recent_reviews ?? []).map((entry) => ({
        key: `review-${entry.created_at}-${entry.project?.id ?? 0}`,
        title: `${statusLabels[entry.status]?.label ?? entry.status}`,
        subtitle: entry.reviewer?.name
            ? `oleh ${entry.reviewer.name}`
            : undefined,
        projectId: entry.project?.id,
        projectLabel: entry.project
            ? `${entry.project.project_code} — ${entry.project.project_name}`
            : undefined,
        createdAt: entry.created_at,
    }));
});

const chartOptions = computed<ApexOptions>(() => ({
    chart: {
        type: 'area',
        height: 320,
        toolbar: { show: false },
        fontFamily: 'inherit',
    },
    stroke: { curve: 'smooth', width: 2 },
    fill: {
        type: 'gradient',
        gradient: { opacityFrom: 0.35, opacityTo: 0.05 },
    },
    colors: ['#6366f1'],
    dataLabels: { enabled: false },
    xaxis: {
        categories: monthly.value.map((entry) => entry.month),
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            formatter: (value: string | number) => {
                const [year, month] = String(value ?? '').split('-');

                return year && month
                    ? `${month}/${year.slice(2)}`
                    : String(value ?? '');
            },
        },
    },
    yaxis: {
        labels: {
            formatter: (value?: number) => {
                if (value == null || Number.isNaN(value)) {
                    return '';
                }

                return String(Math.trunc(value));
            },
        },
    },
    grid: { borderColor: 'var(--border)' },
    tooltip: {
        y: {
            formatter: (value?: number) =>
                value == null
                    ? ''
                    : `${value} ${isApplicant.value ? 'proyek' : 'review'}`,
        },
    },
}));

const chartSeries = computed(() => [
    {
        name: isApplicant.value ? 'Pengajuan' : 'Review',
        data: monthly.value.map((entry) => entry.total),
    },
]);

async function loadDashboard() {
    loading.value = true;

    try {
        const { data } = await api.get<{
            dashboard: ApplicantDashboard | ReviewerDashboard;
        }>('/dashboard');

        if (isApplicant.value) {
            applicant.value = data.dashboard as ApplicantDashboard;
        } else {
            reviewer.value = data.dashboard as ReviewerDashboard;
        }
    } finally {
        loading.value = false;
    }
}

onMounted(loadDashboard);
</script>

<template>
    <PageHeader
        title="Dashboard"
        description="Ringkasan aktivitas pengajuan proyek Anda."
    />

    <div v-if="loading" class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Skeleton v-for="index in 6" :key="index" class="h-24" />
        </div>
        <Skeleton class="h-80" />
    </div>

    <template v-else>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="(value, status) in summary" :key="status">
                <CardContent class="flex items-center justify-between p-6">
                    <div>
                        <p class="text-sm text-muted-foreground">
                            {{ statusLabels[status]?.label ?? status }}
                        </p>
                        <p class="mt-1 text-3xl font-bold">{{ value }}</p>
                    </div>
                    <StatusBadge
                        v-if="statusLabels[status]"
                        :status="statusLabels[status]"
                    />
                </CardContent>
            </Card>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle class="text-base">
                        {{
                            isApplicant
                                ? 'Pengajuan 12 bulan terakhir'
                                : 'Review 12 bulan terakhir'
                        }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <VueApexCharts
                        type="area"
                        height="320"
                        :options="chartOptions"
                        :series="chartSeries"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">
                        {{
                            isApplicant ? 'Aktivitas terbaru' : 'Review terbaru'
                        }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <EmptyState
                        v-if="recentItems.length === 0"
                        title="Belum ada aktivitas"
                        description="Aktivitas terbaru akan muncul di sini."
                    />
                    <ol v-else class="space-y-4">
                        <li v-for="entry in recentItems" :key="entry.key">
                            <div class="flex items-start gap-3">
                                <div
                                    class="mt-0.5 size-2 shrink-0 rounded-full bg-primary/10 text-primary"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium">
                                        {{ entry.title }}
                                        <span
                                            v-if="entry.subtitle"
                                            class="text-muted-foreground"
                                        >
                                            {{ entry.subtitle }}
                                        </span>
                                    </p>
                                    <RouterLink
                                        v-if="entry.projectId"
                                        :to="{
                                            name: 'projects.show',
                                            params: { id: entry.projectId },
                                        }"
                                        class="block truncate text-sm text-muted-foreground hover:text-foreground"
                                    >
                                        {{ entry.projectLabel }}
                                    </RouterLink>
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatDateTime(entry.createdAt) }}
                                    </p>
                                </div>
                            </div>
                        </li>
                    </ol>
                </CardContent>
            </Card>
        </div>
    </template>
</template>
