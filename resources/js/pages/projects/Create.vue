<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { toast } from 'vue-sonner';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import api, { getErrorMessage } from '@/lib/api';
import type { ProjectDetail } from '@/types/project';

const router = useRouter();

const name = ref('');
const description = ref('');
const submitting = ref(false);

async function submit() {
    submitting.value = true;

    try {
        const { data } = await api.post<{ project: ProjectDetail }>(
            '/projects',
            {
                project_name: name.value,
                description: description.value || null,
            },
        );
        toast.success('Proyek berhasil dibuat.');
        router.push({ name: 'projects.show', params: { id: data.project.id } });
    } catch (error) {
        toast.error(getErrorMessage(error));
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <PageHeader
        title="Buat proyek"
        description="Lengkapi informasi dasar proyek untuk memulai pengajuan."
    />

    <div class="mx-auto max-w-2xl">
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Informasi proyek</CardTitle>
                <CardDescription
                    >Kode proyek akan dibuat secara otomatis.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="project_name">Nama proyek</Label>
                        <Input
                            id="project_name"
                            v-model="name"
                            type="text"
                            placeholder="Contoh: Pembangunan Jalan Poros Kecamatan"
                            autofocus
                            required
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="description">Deskripsi</Label>
                        <Textarea
                            id="description"
                            v-model="description"
                            rows="5"
                            placeholder="Jelaskan secara singkat cakupan proyek..."
                        />
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="router.back()"
                        >
                            Batal
                        </Button>
                        <Button type="submit" :disabled="submitting">
                            <Spinner v-if="submitting" />
                            Simpan proyek
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
