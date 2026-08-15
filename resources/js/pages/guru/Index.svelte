<script lang="ts">
    import { Badge, Button } from '@sveltestrap/sveltestrap';

    let { jadwal_saya, tanggal_sekarang } = $props();
</script>

{#if jadwal_saya}
    <div class="my-2 mb-3">
        <div class="fs-4 fw-bold text-dark">
            Jadwal Mengajar Hari Ini {tanggal_sekarang}
        </div>
        <p class="fs-6 text-muted">Berikut adalah jadwal anda sekarang</p>
    </div>
    <div class="d-flex flex-column gap-1">
        {#each jadwal_saya as jadwal (jadwal.id)}
            <div
                class="d-flex rounded flex-column bg-white  shadow-sm border-5 border-primary border-start"
                class:border-success={jadwal?.berlangsung}
                class:bg-success-subtle={jadwal?.berlangsung}
            >
                <div class="p-3 d-flex flex-column">
                   <div class="d-flex align-items-center gap-2">
                     <span class="fw-bold text-success fs-4"
                        >{jadwal?.kelas} ( {jadwal?.jam_ke})</span
                    >
                     <div>
                        {#if jadwal?.berlangsung}
                            <Badge color="success">Berlangsung</Badge>
                        {/if}
                        {#if jadwal?.akan_datang}
                            <Badge color="warning">Akan Datang</Badge>
                        {/if}
                        {#if jadwal?.sudah_selesai}
                            <Badge color="primary">Sudah Selesai</Badge>
                        {/if}
                    </div>
                   </div>
                    <span class="fs-6 fw-bolder">
                        {jadwal?.mulai} - {jadwal?.selesai}
                    </span>
                    <span class="fs-5 d-block mb-1 lh-base text-muted">
                        {jadwal?.matpel}
                    </span>
                   
                    <div class="mt-2">
                        <Button color="primary" size="sm">Buka Kelas</Button>
                        <Button color="info" size="sm">Presensi</Button>
                    </div>
                </div>
            </div>
        {/each}
    </div>
{/if}
