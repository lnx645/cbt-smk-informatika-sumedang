import { createInertiaApp } from '@inertiajs/svelte';
import DashboardLayout from '@/layouts/DashboardLayout.svelte';
import MainLayout from '@/layouts/MainLayout.svelte';
import 'bootstrap-icons/font/bootstrap-icons.css';
import { initializeFlashToast } from './lib/flash-toast.svelte';
import PembelajaranLayout from './layouts/pembelajaran/PembelajaranLayout.svelte';
import AdminLayout from './layouts/admin/AdminLayout.svelte';
import QuizLayout from './layouts/quiz/QuizLayout.svelte';
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout(name, page) {
        let url = page.url;
        if (url.startsWith('/manage')) {
            return AdminLayout;
        } else if (url.startsWith('/m/ujian')) {
            return QuizLayout;
        } else if (url.startsWith('/m/pembelajaran')) {
            return PembelajaranLayout;
        }
        return MainLayout;
    },
    progress: {
        color: '#1d4ed8',
    },
});

initializeFlashToast();
