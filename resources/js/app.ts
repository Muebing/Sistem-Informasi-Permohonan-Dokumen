import { createPinia } from 'pinia';
import { createApp } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import router from '@/router';
import App from './App.vue';

import '../css/app.css';

initializeTheme();

createApp(App).use(createPinia()).use(router).mount('#app');
