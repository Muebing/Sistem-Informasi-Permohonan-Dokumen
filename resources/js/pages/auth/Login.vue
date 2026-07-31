<script setup lang="ts">
import { computed, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
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
const route = useRoute();

const email = ref('');
const password = ref('');
const submitting = ref(false);

const redirectTo = computed(() => {
    const redirect = route.query.redirect;

    return typeof redirect === 'string' ? redirect : '/';
});

async function submit() {
    submitting.value = true;

    try {
        await auth.login(email.value, password.value);
        toast.success('Berhasil masuk. Selamat datang kembali!');
        router.push(redirectTo.value);
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
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Kata sandi</Label>
                        <RouterLink
                            :to="{ name: 'forgot-password' }"
                            class="text-sm text-primary hover:underline"
                        >
                            Lupa kata sandi?
                        </RouterLink>
                    </div>
                    <Input
                        id="password"
                        v-model="password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        required
                    />
                </div>
                <Button type="submit" class="w-full" :disabled="submitting">
                    <Spinner v-if="submitting" />
                    Masuk
                </Button>
            </form>
        </CardContent>
        <CardFooter class="justify-center border-t pt-4 text-sm">
            <span class="text-muted-foreground">Belum punya akun?</span>
            <RouterLink
                :to="{ name: 'register' }"
                class="ml-1 text-primary hover:underline"
            >
                Daftar
            </RouterLink>
        </CardFooter>
    </Card>
</template>
