<script setup lang="ts">
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import { toast } from 'vue-sonner';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import api, { getErrorMessage } from '@/lib/api';

const email = ref('');
const submitted = ref(false);
const submitting = ref(false);

async function submit() {
    submitting.value = true;

    try {
        await api.post('/auth/forgot-password', { email: email.value });
        submitted.value = true;
        toast.success('Tautan reset kata sandi telah dikirim ke email Anda.');
    } catch (error) {
        toast.error(getErrorMessage(error));
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Alert v-if="submitted" class="mb-4">
        <AlertDescription>
            Jika email terdaftar, tautan untuk mengatur ulang kata sandi akan
            dikirim ke
            {{ email }}.
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
                        placeholder="nama@contoh.com"
                        autofocus
                        required
                    />
                </div>
                <Button
                    type="submit"
                    class="w-full"
                    :disabled="submitting || submitted"
                >
                    <Spinner v-if="submitting" />
                    Kirim tautan reset
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
