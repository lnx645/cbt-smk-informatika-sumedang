<script lang="ts">
    import {
        Badge,
        Button,
        Card,
        CardBody,
        TabContent,
        TabPane,
    } from '@sveltestrap/sveltestrap';
    import { router } from '@inertiajs/svelte';
    import PengajarController from '@/actions/App/Http/Controllers/Admin/PengajarController';
    import PageHeader from '@/components/PageHeader.svelte';

    interface MatpelItem {
        nama: string | null;
    }

    interface KelasItem {
        nama_kelas: string;
        matpels: MatpelItem[];
    }

    interface Props {
        nama: string;
        nip: string | null;
        kelas: KelasItem[];
    }

    let { nama = '', nip = null, kelas = [] }: Props = $props();

    const hasClasses = $derived(kelas.length > 0);

    const guruInisial = $derived(
        nama
            ? nama
                  .split(',')[0]
                  .split(' ')
                  .slice(0, 2)
                  .map((w: string) => w[0])
                  .join('')
                  .toUpperCase()
            : '',
    );

    const validMatpels = (item: KelasItem) =>
        item.matpels.filter((m) => m.nama !== null && m.nama !== undefined);

    function goBack() {
        router.visit(PengajarController.index().url);
    }
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Atur Guru Kelas"
        subtitle={`Kelola plotting mata pelajaran untuk ${nama || '-'}`}
    >
        {#snippet actions()}
            <Button color="secondary" outline size="sm" onclick={goBack}>
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Pengajar
            </Button>
        {/snippet}
    </PageHeader>

    <div class="card border rounded-1 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <div
                    class="rounded-circle bg-secondary-subtle text-secondary-emphasis d-flex align-items-center justify-content-center fw-semibold"
                    style="width:56px;height:56px;font-size:1.1rem"
                >
                    {guruInisial}
                </div>
                <div>
                    <div class="fw-semibold text-body fs-5">{nama}</div>
                    <div class="text-secondary small">NIP {nip ?? '-'}</div>
                </div>
            </div>
        </div>
    </div>

    {#if hasClasses}
        <TabContent>
            {#each kelas as item, key (key)}
                <TabPane tab={item.nama_kelas} tabId={key} active={key === 0}>
                    <div class="mt-3">
                        {#if validMatpels(item).length}
                            <div
                                class="d-flex flex-wrap gap-2 align-items-center"
                            >
                                {#each validMatpels(item) as matpel (matpel.nama)}
                                    <div
                                        class="d-inline-flex align-items-center gap-2 bg-white border rounded px-3 py-2 shadow-sm"
                                    >
                                        <i class="bi bi-book-half text-primary"
                                        ></i>
                                        <span class="text-sm fw-semibold"
                                            >{matpel?.nama}</span
                                        >
                                    </div>
                                {/each}
                            </div>
                        {:else}
                            <div
                                class="d-flex flex-column align-items-center justify-content-center text-center py-4 border rounded bg-light"
                            >
                                <i
                                    class="bi bi-book text-secondary"
                                    style="font-size: 2rem"
                                ></i>
                                <p class="text-secondary small mb-0 mt-2">
                                    Belum ada mata pelajaran yang ditingkatkan.
                                </p>
                            </div>
                        {/if}
                    </div>
                </TabPane>
            {/each}
        </TabContent>
    {:else}
        <Card class="border rounded-1 shadow-sm">
            <CardBody class="py-5">
                <div class="text-center text-secondary">
                    <i class="bi bi-emoji-frown" style="font-size: 3rem"></i>
                    <p class="mt-3 mb-0">
                        Belum ada kelas yang diampu oleh guru ini.
                    </p>
                </div>
            </CardBody>
        </Card>
    {/if}
</div>
