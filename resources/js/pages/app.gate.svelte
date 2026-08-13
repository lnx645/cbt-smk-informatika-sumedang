<script>
    import LearningModuleController from '@/actions/App/Http/Controllers/Module/Learning/LearningModuleController';
    import ModulUjianController from '@/actions/App/Http/Controllers/Module/Ujian/ModulUjianController';
    import AppShellLayout from '@/layouts/AppShellLayout.svelte';
    let sd = $props();

    let navItems = [
        {
            label: 'Pembelajaran',
            icon: 'bi-journal-richtext',
            href: ModulUjianController().url,
        },
        {
            href: LearningModuleController().url+"?sd",
            label: 'Ujian (CBT)',
            icon: 'bi-file-earmark-text-fill',
        },
    ];
</script>

<AppShellLayout {navItems} user={sd.auth?.user}>
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Incidunt ad ut ex
    vel, recusandae mollitia cupiditate dolores nostrum perspiciatis natus a
    ipsa consectetur suscipit ducimus expedita perferendis itaque alias placeat?
</AppShellLayout>
