<script lang="ts">
    import {
        Badge,
        Modal,
        ModalBody,
        Button,
        Card,
        CardBody,
        TabContent,
        TabPane,
        Input,
    } from '@sveltestrap/sveltestrap';
    import { router, useForm, Form } from '@inertiajs/svelte';
    import PengajarController from '@/actions/App/Http/Controllers/Admin/PengajarController';
    import PageHeader from '@/components/PageHeader.svelte';
    import GuruKelasController from '@/actions/App/Http/Controllers/Admin/GuruKelasController';

    interface MatpelItem {
        nama: string | null;
    }

    interface KelasItem {
        nama_kelas: string;
        matpels: MatpelItem[];
    }

    interface Props {
        nama: string;
        guru_id: number;
        nip: string | null;
        matpels: any;
        kelas: KelasItem[];
        kelas_list: any;
    }
    let {
        nama = '',
        nip = null,
        kelas = [],
        matpels,
        guru_id,
        kelas_list,
    }: Props = $props();
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
        item.matpels.filter(
            (m) => m.nama !== null && m.nama !== undefined,
        );

    function goBack() {
        router.visit(PengajarController.index().url);
    }

    let formTambah = useForm({
        kelas_id: '',
        guru_id: '',
        matpel_id: '',
    });

    let modalBaru = $state(false);
</script>

<div class="container-fluid px-0">
    <PageHeader
        title="Atur Guru Kelas"
        subtitle={`Kelola plotting mata pelajaran untuk ${nama || '-'}`}
    >
        {#snippet actions()}
            <Button
                color="secondary"
                outline
                size="sm"
                onclick={goBack}
            >
                <i class="bi bi-arrow-left me-1"></i>
                Kembali ke Pengajar
            </Button>
            <Button
                onclick={() => (modalBaru = true)}
                size="sm"
                color="primary">Tambah Kelas Baru</Button
            >
        {/snippet}
    </PageHeader>

    <Modal
        isOpen={modalBaru}
        toggle={() => (modalBaru = false)}
        header={'Tambah Baru'}
    >
        <ModalBody>
            <Form
                action={GuruKelasController.store({
                    id: guru_id,
                })}
                method={'post'}
                onSuccess={(e) => {
                    modalBaru = false;
                }}
            >
                <div class="form-group">
                    <label
                        for="matpel"
                        class="form-label text-xs fw-semibold"
                        >Mata Pelajaran</label
                    >
                    <Input
                        name="matpel_id"
                        id={'matpel'}
                        bsSize="sm"
                        type="select"
                    >
                        {#each matpels as item, key (key)}
                            <option value={item?.id}>
                                {item?.name}
                            </option>
                        {/each}
                    </Input>
                </div>
                <div class="form-group">
                    <label
                        for=""
                        class="form-label text-xs fw-semibold"
                    >
                        Kelas
                    </label>
                    <Input name="kelas_id" bsSize="sm" type="select">
                        {#each kelas_list as item, key (key)}
                            <option value={item?.id}>
                                {item?.name}
                            </option>
                        {/each}
                    </Input>
                </div>
                <div class="mt-3">
                    <Button size="sm" color="primary">Save</Button>
                </div>
            </Form>
        </ModalBody>
    </Modal>

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
                    <div class="fw-semibold text-body fs-5">
                        {nama}
                    </div>
                    <div class="text-secondary small">
                        NIP
                        {nip ?? '-'}
                    </div>
                </div>
            </div>
        </div>
    </div>
    {#if hasClasses}
        <TabContent>
            {#each kelas as item, key (key)}
                <TabPane
                    tab={item.nama_kelas}
                    tabId={key}
                    active={key === 0}
                >
                    <div class="mt-3">
                        {#if validMatpels(item).length}
                            <div
                                class="d-flex flex-wrap gap-2 align-items-center"
                            >
                                {#each validMatpels(item) as matpel, key (key)}
                                    <div
                                        class="d-inline-flex align-items-center gap-2 bg-white border rounded px-3 py-2"
                                    >
                                        <i
                                            class="bi bi-book-half text-primary"
                                        ></i>
                                        <span
                                            class="text-sm fw-semibold"
                                            >{matpel?.nama}</span
                                        >
                                        <i class="bi bi-trash text-sm"
                                        ></i>
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
                                <p
                                    class="text-secondary small mb-0 mt-2"
                                >
                                    Belum ada mata pelajaran yang
                                    ditingkatkan.
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
                    <i
                        class="bi bi-emoji-frown"
                        style="font-size: 3rem"
                    ></i>
                    <p class="mt-3 mb-0">
                        Belum ada kelas yang diampu oleh guru ini.
                    </p>
                </div>
            </CardBody>
        </Card>
    {/if}
</div>
