import { createInertiaApp } from '@inertiajs/svelte';
import DashboardLayout from '@/layouts/DashboardLayout.svelte';
import MainLayout from '@/layouts/MainLayout.svelte';
import "bootstrap-icons/font/bootstrap-icons.css";
import { initializeFlashToast } from './lib/flash-toast.svelte';
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout(name, page) {
        let url = page.url;
        if (url.startsWith('/manage')) {
            return DashboardLayout;
        }
        return MainLayout;
    },
    progress: {
        color: '#1d4ed8',
    },
});

initializeFlashToast()