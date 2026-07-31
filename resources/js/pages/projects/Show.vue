<script setup lang="ts">
import {
    ArrowLeft,
    CheckCircle2,
    Download,
    FileText,
    History as HistoryIcon,
    Loader2,
    Pencil,
    Send,
    Trash2,
    Upload,
    XCircle,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { toast } from 'vue-sonner';
import EmptyState from '@/components/EmptyState.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import api, { getErrorMessage } from '@/lib/api';
import { formatDate, formatDateTime } from '@/lib/format';
import { useAuthStore } from '@/stores/auth';
import type {
    HistoryEntry,
    ProjectDetail,
    ProjectDocument,
} from '@/types/project';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const projectId = Number(route.params.id);
const project = ref<ProjectDetail | null>(null);
const timeline = ref<HistoryEntry[]>([]);
const loading = ref(true);

const editOpen = ref(false);
const deleteOpen = ref(false);
const reviewOpen = ref(false);
const uploading = ref(false);
const submitting = ref(false);
const deciding = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const editName = ref('');
const editDescription = ref('');
const reviewNotes = ref('');
const reviewDecision = ref<'approved' | 'rejected' | 'revision'>('approved');

const isOwner = computed(() => auth.user?.id === project.value?.applicant?.id);
const canUpload = computed(
    () =>
        isOwner.value &&
        (project.value?.status.value === 'draft' ||
            project.value?.status.value === 'revision'),
);
const canEdit = computed(
    () =>
        isOwner.value &&
        (project.value?.status.value === 'draft' ||
            project.value?.status.value === 'revision'),
);
const canSubmit = computed(
    () =>
        isOwner.value &&
        (project.value?.status.value === 'draft' ||
            project.value?.status.value === 'revision'),
);
const canDelete = computed(
    () => isOwner.value && project.value?.status.value === 'draft',
);
const canStartReview = computed(
    () => auth.isReviewer && project.value?.status.value === 'submitted',
);
const canDecide = computed(
    () => auth.isReviewer && project.value?.status.value === 'under_review',
);

async function load() {
    loading.value = true;

    try {
        const [{ data }, historyResponse] = await Promise.all([
            api.get<{ project: ProjectDetail }>(`/projects/${projectId}`),
            api.get<{ history: HistoryEntry[] }>(
                `/projects/${projectId}/history`,
            ),
        ]);
        project.value = data.project;
        timeline.value = historyResponse.data.history;
        editName.value = data.project.project_name;
        editDescription.value = data.project.description ?? '';
    } catch (error) {
        toast.error(getErrorMessage(error));
    } finally {
        loading.value = false;
    }
}

async function updateProject() {
    try {
        const { data } = await api.put<{ project: ProjectDetail }>(
            `/projects/${projectId}`,
            {
                project_name: editName.value,
                description: editDescription.value || null,
            },
        );
        project.value = data.project;
        editOpen.value = false;
        toast.success('Proyek berhasil diperbarui.');
    } catch (error) {
        toast.error(getErrorMessage(error));
    }
}

async function deleteProject() {
    try {
        await api.delete(`/projects/${projectId}`);
        toast.success('Proyek berhasil dihapus.');
        router.push({ name: 'projects.index' });
    } catch (error) {
        toast.error(getErrorMessage(error));
    }
}

async function submitProject() {
    submitting.value = true;

    try {
        const { data } = await api.post<{ project: ProjectDetail }>(
            `/projects/${projectId}/submit`,
        );
        project.value = data.project;
        toast.success('Proyek telah diajukan untuk direview.');
    } catch (error) {
        toast.error(getErrorMessage(error));
    } finally {
        submitting.value = false;
    }
}

async function startReview() {
    submitting.value = true;

    try {
        const { data } = await api.post<{ project: ProjectDetail }>(
            `/projects/${projectId}/start-review`,
        );
        project.value = data.project;
        toast.success('Review dimulai.');
    } catch (error) {
        toast.error(getErrorMessage(error));
    } finally {
        submitting.value = false;
    }
}

async function decideReview() {
    deciding.value = true;

    try {
        await api.post('/reviews', {
            project_id: projectId,
            status: reviewDecision.value,
            notes: reviewNotes.value || null,
        });
        reviewOpen.value = false;
        reviewNotes.value = '';
        toast.success('Keputusan review tersimpan.');
        await load();
    } catch (error) {
        toast.error(getErrorMessage(error));
    } finally {
        deciding.value = false;
    }
}

function openFilePicker() {
    fileInput.value?.click();
}

async function uploadDocument(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    uploading.value = true;

    try {
        const formData = new FormData();
        formData.append('document', file);

        const { data } = await api.post<{ project: ProjectDetail }>(
            `/projects/${projectId}/documents`,
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } },
        );
        project.value = data.project;
        toast.success('Dokumen berhasil diunggah.');
    } catch (error) {
        toast.error(getErrorMessage(error));
    } finally {
        uploading.value = false;
        input.value = '';
    }
}

async function deleteDocument(doc: ProjectDocument) {
    try {
        const { data } = await api.delete<{ project: ProjectDetail }>(
            `/projects/${projectId}/documents/${doc.id}`,
        );
        project.value = data.project;
        toast.success('Dokumen berhasil dihapus.');
    } catch (error) {
        toast.error(getErrorMessage(error));
    }
}

async function downloadDocument(doc: ProjectDocument) {
    try {
        const response = await api.get<Blob>(doc.download_url, {
            responseType: 'blob',
        });
        const url = URL.createObjectURL(response.data);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = doc.original_name;
        anchor.click();
        URL.revokeObjectURL(url);
    } catch (error) {
        toast.error(getErrorMessage(error));
    }
}

onMounted(load);
</script>

<template>
    <Button variant="ghost" size="sm" class="mb-4" @click="router.back()">
        <ArrowLeft />
        Kembali
    </Button>

    <div v-if="loading" class="space-y-6">
        <Skeleton class="h-32" />
        <Skeleton class="h-64" />
    </div>

    <template v-else-if="project">
        <Card>
            <CardHeader
                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <Badge variant="outline" class="font-mono">{{
                            project.project_code
                        }}</Badge>
                        <StatusBadge :status="project.status" />
                    </div>
                    <CardTitle class="mt-3 text-xl">{{
                        project.project_name
                    }}</CardTitle>
                    <CardDescription
                        v-if="project.description"
                        class="mt-1 whitespace-pre-line"
                    >
                        {{ project.description }}
                    </CardDescription>
                    <div class="mt-3 space-y-0.5 text-sm text-muted-foreground">
                        <p v-if="project.applicant">
                            Pemohon: {{ project.applicant.name }} ({{
                                project.applicant.email
                            }})
                        </p>
                        <p>Dibuat: {{ formatDateTime(project.created_at) }}</p>
                        <p v-if="project.submitted_at">
                            Diajukan: {{ formatDateTime(project.submitted_at) }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <template v-if="canUpload">
                        <input
                            ref="fileInput"
                            type="file"
                            class="hidden"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.zip"
                            @change="uploadDocument"
                        />
                        <Button
                            size="sm"
                            :disabled="uploading"
                            @click="openFilePicker"
                        >
                            <Loader2 v-if="uploading" class="animate-spin" />
                            <Upload v-else />
                            Unggah dokumen
                        </Button>
                    </template>
                    <Button
                        v-if="canSubmit"
                        size="sm"
                        :disabled="submitting"
                        @click="submitProject"
                    >
                        <Loader2 v-if="submitting" class="animate-spin" />
                        <Send v-else />
                        Ajukan review
                    </Button>
                    <Button
                        v-if="canEdit"
                        size="sm"
                        variant="outline"
                        @click="editOpen = true"
                    >
                        <Pencil />
                        Edit
                    </Button>
                    <Button
                        v-if="canDelete"
                        size="sm"
                        variant="destructive"
                        @click="deleteOpen = true"
                    >
                        <Trash2 />
                        Hapus
                    </Button>
                    <Button
                        v-if="canStartReview"
                        size="sm"
                        :disabled="submitting"
                        @click="startReview"
                    >
                        <Loader2 v-if="submitting" class="animate-spin" />
                        Mulai review
                    </Button>
                    <Button
                        v-if="canDecide"
                        size="sm"
                        @click="reviewOpen = true"
                    >
                        <CheckCircle2 />
                        Beri keputusan
                    </Button>
                </div>
            </CardHeader>
        </Card>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <FileText class="size-4 text-muted-foreground" />
                        Dokumen ({{ project.documents.length }})
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <EmptyState
                        v-if="project.documents.length === 0"
                        :icon="FileText"
                        title="Belum ada dokumen"
                        description="Unggah dokumen pendukung proyek Anda."
                    />
                    <ul v-else class="divide-y">
                        <li
                            v-for="doc in project.documents"
                            :key="doc.id"
                            class="flex items-center gap-3 py-3"
                        >
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground"
                            >
                                <FileText class="size-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    {{ doc.original_name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ doc.size_human }} ·
                                    {{ formatDate(doc.created_at) }}
                                </p>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                @click="downloadDocument(doc)"
                            >
                                <Download />
                                <span class="sr-only">Unduh</span>
                            </Button>
                            <Button
                                v-if="canUpload"
                                variant="ghost"
                                size="icon-sm"
                                class="text-destructive"
                                @click="deleteDocument(doc)"
                            >
                                <Trash2 />
                                <span class="sr-only">Hapus</span>
                            </Button>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <HistoryIcon class="size-4 text-muted-foreground" />
                        Review ({{ project.reviews.length }})
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <EmptyState
                        v-if="project.reviews.length === 0"
                        :icon="HistoryIcon"
                        title="Belum ada review"
                        description="Hasil review akan muncul di sini."
                    />
                    <ol v-else class="space-y-5">
                        <li v-for="review in project.reviews" :key="review.id">
                            <div class="flex items-start gap-3">
                                <StatusBadge
                                    :status="review.status"
                                    size="sm"
                                    :dot="false"
                                />
                                <div class="min-w-0 flex-1">
                                    <p v-if="review.notes" class="text-sm">
                                        {{ review.notes }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            review.reviewer?.name ?? 'Reviewer'
                                        }}
                                        ·
                                        {{ formatDateTime(review.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </li>
                    </ol>
                </CardContent>
            </Card>
        </div>

        <Card class="mt-6">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <HistoryIcon class="size-4 text-muted-foreground" />
                    Riwayat aktivitas
                </CardTitle>
            </CardHeader>
            <CardContent>
                <EmptyState
                    v-if="timeline.length === 0"
                    title="Belum ada riwayat"
                    description="Riwayat aktivitas proyek akan muncul di sini."
                />
                <ol v-else class="relative space-y-6 border-l pl-6">
                    <li
                        v-for="(entry, index) in timeline"
                        :key="index"
                        class="relative"
                    >
                        <span
                            class="absolute top-1.5 -left-6.5 size-2 rounded-full bg-primary"
                        />
                        <p class="text-sm font-medium">{{ entry.action }}</p>
                        <p
                            v-if="entry.notes"
                            class="text-sm text-muted-foreground"
                        >
                            {{ entry.notes }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ entry.user_name ?? 'Sistem' }} ·
                            {{ formatDateTime(entry.created_at) }}
                        </p>
                    </li>
                </ol>
            </CardContent>
        </Card>

        <Dialog v-model:open="editOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Edit proyek</DialogTitle>
                    <DialogDescription
                        >Perbarui informasi proyek Anda.</DialogDescription
                    >
                </DialogHeader>
                <form
                    id="edit-project-form"
                    class="space-y-4"
                    @submit.prevent="updateProject"
                >
                    <div class="space-y-2">
                        <Label for="edit-name">Nama proyek</Label>
                        <Input id="edit-name" v-model="editName" required />
                    </div>
                    <div class="space-y-2">
                        <Label for="edit-description">Deskripsi</Label>
                        <Textarea
                            id="edit-description"
                            v-model="editDescription"
                            rows="4"
                        />
                    </div>
                </form>
                <DialogFooter>
                    <Button variant="outline" @click="editOpen = false"
                        >Batal</Button
                    >
                    <Button type="submit" form="edit-project-form"
                        >Simpan</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="deleteOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Hapus proyek?</DialogTitle>
                    <DialogDescription>
                        Proyek ini beserta seluruh dokumennya akan dihapus
                        permanen. Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteOpen = false"
                        >Batal</Button
                    >
                    <Button variant="destructive" @click="deleteProject"
                        >Hapus</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="reviewOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Keputusan review</DialogTitle>
                    <DialogDescription>
                        Pilih hasil review dan sertakan catatan untuk pemohon.
                    </DialogDescription>
                </DialogHeader>
                <form
                    id="review-form"
                    class="space-y-4"
                    @submit.prevent="decideReview"
                >
                    <div class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            :class="
                                reviewDecision === 'approved' &&
                                'border-emerald-300 bg-emerald-50 text-emerald-700'
                            "
                            @click="reviewDecision = 'approved'"
                        >
                            <CheckCircle2 />
                            Setujui
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            :class="
                                reviewDecision === 'revision' &&
                                'border-amber-300 bg-amber-50 text-amber-700'
                            "
                            @click="reviewDecision = 'revision'"
                        >
                            <XCircle />
                            Minta revisi
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            :class="
                                reviewDecision === 'rejected' &&
                                'border-rose-300 bg-rose-50 text-rose-700'
                            "
                            @click="reviewDecision = 'rejected'"
                        >
                            <XCircle />
                            Tolak
                        </Button>
                    </div>
                    <div class="space-y-2">
                        <Label for="review-notes">Catatan</Label>
                        <Textarea
                            id="review-notes"
                            v-model="reviewNotes"
                            rows="4"
                            placeholder="Catatan untuk pemohon..."
                            required
                        />
                    </div>
                </form>
                <DialogFooter>
                    <Button variant="outline" @click="reviewOpen = false"
                        >Batal</Button
                    >
                    <Button
                        type="submit"
                        form="review-form"
                        :disabled="deciding || reviewNotes.trim() === ''"
                    >
                        <Spinner v-if="deciding" />
                        Simpan keputusan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </template>
</template>
