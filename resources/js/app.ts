import { createInertiaApp } from '@inertiajs/svelte';
import MainLayout from '@/layouts/MainLayout.svelte';
import 'bootstrap-icons/font/bootstrap-icons.css';
import { initializeFlashToast } from './lib/flash-toast.svelte';
import AdminLayout from './layouts/admin/AdminLayout.svelte';
import UserLayout from './layouts/app/UserLayout.svelte';
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout(name, page) {
        let url = page.url;
        if (url.startsWith('/manage')) {
            return AdminLayout;
        } else if (url.startsWith('/app')) {
            return UserLayout;
        }
        return MainLayout;
    },
    progress: {
        color: '#1d4ed8',
    },
});

initializeFlashToast();
