import { createApp } from 'vue';
import App from './App.vue';
import axios from 'axios';
import './bootstrap';
import router from './router';
import i18n, { initI18n } from './i18n';
import { applyTheme } from './composables/useTheme';

axios.defaults.baseURL = '/api/v1';

axios.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

applyTheme();

const app = createApp(App);
app.use(router);
app.use(i18n);
initI18n();
app.mount('#app');
