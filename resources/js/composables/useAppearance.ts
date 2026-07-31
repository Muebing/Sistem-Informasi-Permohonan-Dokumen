import { onMounted, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types/ui';

const STORAGE_KEY = 'appearance';

const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

function loadInitialAppearance(): Appearance {
    const stored = localStorage.getItem(STORAGE_KEY);

    return stored === 'light' || stored === 'dark' || stored === 'system'
        ? stored
        : 'system';
}

function resolve(value: Appearance): ResolvedAppearance {
    if (value === 'system') {
        return mediaQuery.matches ? 'dark' : 'light';
    }

    return value;
}

function apply(value: ResolvedAppearance): void {
    document.documentElement.classList.toggle('dark', value === 'dark');
    resolved.value = value;
}

const appearance = ref<Appearance>(loadInitialAppearance());
const resolved = ref<ResolvedAppearance>(resolve(appearance.value));

export function initializeTheme(): void {
    apply(resolve(appearance.value));
}

export function useAppearance() {
    onMounted(() => {
        mediaQuery.addEventListener('change', () => {
            if (appearance.value === 'system') {
                apply(resolve('system'));
            }
        });
    });

    function setAppearance(value: Appearance): void {
        appearance.value = value;
        localStorage.setItem(STORAGE_KEY, value);
        apply(resolve(value));
    }

    return { appearance, resolved, setAppearance };
}
