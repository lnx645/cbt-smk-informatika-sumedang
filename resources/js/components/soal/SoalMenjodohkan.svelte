<script lang="ts">
    import { Button, Card, CardBody } from '@sveltestrap/sveltestrap';

    type Pasangan = { kiri: string; kanan: string };
    let { pasanganList = $bindable() }: { pasanganList: Pasangan[] } = $props();

    function tambah() { pasanganList = [...pasanganList, { kiri: '', kanan: '' }]; }
    function hapus(i: number) { pasanganList = pasanganList.filter((_, x) => x !== i); }
</script>

<div class="mb-2"><span class="fw-semibold">Pasangan</span></div>
{#each pasanganList as _, i}
    <Card class="mb-2"><CardBody class="py-2">
        <div class="row g-2 align-items-center">
            <div class="col-5"><input class="form-control" bind:value={pasanganList[i].kiri} placeholder="Kiri {i + 1}" /></div>
            <div class="col-auto"><i class="bi bi-arrow-left-right text-muted"></i></div>
            <div class="col-5"><input class="form-control" bind:value={pasanganList[i].kanan} placeholder="Kanan {i + 1}" /></div>
            <div class="col-auto"><Button color="link" class="text-danger p-0" onclick={() => hapus(i)}><i class="bi bi-trash"></i></Button></div>
        </div>
    </CardBody></Card>
{/each}
<Button color="link" size="sm" onclick={tambah} class="mb-3 p-0"><i class="bi bi-plus-circle me-1"></i>Tambah pasangan</Button>
