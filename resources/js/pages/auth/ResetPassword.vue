<script setup lang="ts">
import { ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { toast } from 'vue-sonner';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import api, { getErrorMessage } from '@/lib/api';

const route = useRoute();

const email = ref(
    typeof route.query.email === 'string' ? route.query.email : '',
);
const token = typeof route.query.token === 'string' ? route.query.token : '';
const password = ref('');
const passwordConfirmation = ref('');
const submitted = ref(false);
const submitting = ref(false);

async function submit() {
    submitting.value = true;

    try {
        await api.post('/auth/reset-password', {
            token,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });
        submitted.value = true;
        toast.success('Kata sandi berhasil direset. Silakan masuk.');
    } catch (error) {
        toast.error(getErrorMessage(error));
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold tracking-tight">Atur ulang kata sandi</h1>
        <p class="mt-2 text-sm text-muted-foreground">
            Buat kata sandi baru untuk akun Anda.
        </p>
    </div>

    <Alert v-if="submitted" class="mb-4">
        <AlertDescription>
            Kata sandi Anda telah diperbarui. Silakan masuk dengan kata sandi
            baru.
        </AlertDescription>
    </Alert>

    <Card>
        <CardContent class="pt-6">
            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        v-model="email"
                        type="email"
                        inputmode="email"
                        autocomplete="email"
                        required
                        autofocus
                    />
                </div>
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
                <Button
                    type="submit"
                    class="w-full"
                    :disabled="submitting || submitted"
                >
                    <Spinner v-if="submitting" />
                    Reset kata sandi
                </Button>
            </form>
        </CardContent>
    </Card>

    <p class="mt-4 text-center text-sm text-muted-foreground">
        <RouterLink
            :to="{ name: 'login' }"
            class="text-primary hover:underline"
        >
            Kembali ke halaman masuk
        </RouterLink>
    </p>
</template>
