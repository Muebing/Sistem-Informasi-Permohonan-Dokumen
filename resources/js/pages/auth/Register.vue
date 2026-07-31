<script setup lang="ts">
import { ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { getErrorMessage } from '@/lib/api';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();

const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const submitting = ref(false);

async function submit() {
    submitting.value = true;

    try {
        await auth.register({
            name: name.value,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });
        toast.success('Akun berhasil dibuat. Selamat datang!');
        router.push({ name: 'dashboard' });
    } catch (error) {
        toast.error(getErrorMessage(error));
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Card>
        <CardContent class="pt-6">
            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="name">Nama lengkap</Label>
                    <Input
                        id="name"
                        v-model="name"
                        type="text"
                        autocomplete="name"
                        placeholder="Nama lengkap"
                        autofocus
                        required
                    />
                </div>
                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        v-model="email"
                        type="email"
                        inputmode="email"
                        autocomplete="email"
                        placeholder="nama@contoh.com"
                        required
                    />
                </div>
                <div class="space-y-2">
                    <Label for="password">Kata sandi</Label>
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
                        placeholder="Ulangi kata sandi"
                        required
                    />
                </div>
                <Button type="submit" class="w-full" :disabled="submitting">
                    <Spinner v-if="submitting" />
                    Daftar
                </Button>
            </form>
        </CardContent>
        <CardFooter class="justify-center border-t pt-4 text-sm">
            <span class="text-muted-foreground">Sudah punya akun?</span>
            <RouterLink
                :to="{ name: 'login' }"
                class="ml-1 text-primary hover:underline"
            >
                Masuk
            </RouterLink>
        </CardFooter>
    </Card>
</template>
