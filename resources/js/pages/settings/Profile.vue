<script setup lang="ts">
import { ref } from 'vue';
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
import api, { getErrorMessage } from '@/lib/api';
import { useAuthStore } from '@/stores/auth';
import type { User } from '@/types/auth';

const auth = useAuthStore();

const name = ref(auth.user?.name ?? '');
const email = ref(auth.user?.email ?? '');
const password = ref('');
const passwordConfirmation = ref('');
const savingProfile = ref(false);
const savingPassword = ref(false);

async function saveProfile() {
    savingProfile.value = true;

    try {
        const { data } = await api.put<{ user: User }>('/auth/profile', {
            name: name.value,
            email: email.value,
        });
        auth.user = data.user;
        toast.success('Profil berhasil diperbarui.');
    } catch (error) {
        toast.error(getErrorMessage(error));
    } finally {
        savingProfile.value = false;
    }
}

async function savePassword() {
    savingPassword.value = true;

    try {
        await api.put('/auth/profile', {
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });
        password.value = '';
        passwordConfirmation.value = '';
        toast.success('Kata sandi berhasil diperbarui.');
    } catch (error) {
        toast.error(getErrorMessage(error));
    } finally {
        savingPassword.value = false;
    }
}
</script>

<template>
    <PageHeader
        title="Pengaturan"
        description="Kelola informasi akun dan kata sandi Anda."
    />

    <div class="mx-auto max-w-2xl space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Profil</CardTitle>
                <CardDescription
                    >Perbarui nama dan email akun Anda.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <form class="space-y-4" @submit.prevent="saveProfile">
                    <div class="space-y-2">
                        <Label for="name">Nama lengkap</Label>
                        <Input id="name" v-model="name" type="text" required />
                    </div>
                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="email"
                            type="email"
                            inputmode="email"
                            required
                        />
                    </div>
                    <div class="flex justify-end">
                        <Button type="submit" :disabled="savingProfile">
                            <Spinner v-if="savingProfile" />
                            Simpan profil
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Kata sandi</CardTitle>
                <CardDescription>Gunakan minimal 8 karakter.</CardDescription>
            </CardHeader>
            <CardContent>
                <form class="space-y-4" @submit.prevent="savePassword">
                    <div class="space-y-2">
                        <Label for="password">Kata sandi baru</Label>
                        <Input
                            id="password"
                            v-model="password"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Minimal 8 karakter"
                            required
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="password_confirmation"
                            >Konfirmasi kata sandi</Label
                        >
                        <Input
                            id="password_confirmation"
                            v-model="passwordConfirmation"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Ulangi kata sandi baru"
                            required
                        />
                    </div>
                    <div class="flex justify-end">
                        <Button type="submit" :disabled="savingPassword">
                            <Spinner v-if="savingPassword" />
                            Perbarui kata sandi
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
