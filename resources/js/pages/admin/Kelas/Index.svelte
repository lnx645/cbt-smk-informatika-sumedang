<script lang="ts">
    let {
        kelas_parent = [],
    }: {
        kelas_parent?: Record<string, any>[];
    } = $props();

    let items = $derived(kelas_parent ?? []);
</script>

{#snippet node(item)}
    <li class="kelas-tree__node">
        <div class="kelas-tree__label">
            <i class="bi bi-diagram-3-fill text-primary"></i>
            <span class="fw-semibold">{item.nama}</span>
            {#if item.jurusan}
                <span class="badge text-bg-info ms-2">{item.jurusan.name}</span>
            {/if}
            {#if item.walikelas}
                <span class="text-muted ms-2">Wali: {item.walikelas.nama_lengkap}</span>
            {/if}
        </div>
        {#if item.children && item.children.length}
            <ul class="kelas-tree__children">
                {#each item.children as child (child.id)}
                    {@render node(child)}
                {/each}
            </ul>
        {/if}
    </li>
{/snippet}

<div class="container py-4">
    <h1 class="h4 fw-semibold mb-3">Kelas</h1>

    {#if items.length}
        <ul class="kelas-tree">
            {#each items as parent (parent.id)}
                {@render node(parent)}
            {/each}
        </ul>
    {:else}
        <p class="text-muted">Belum ada data kelas.</p>
    {/if}
</div>

<style>
    .kelas-tree,
    .kelas-tree__children {
        list-style: none;
        padding-left: 1.25rem;
        margin: 0;
    }

    .kelas-tree {
        padding-left: 0;
    }

    .kelas-tree__children {
        border-left: 2px solid #e9ecef;
        margin-left: 0.5rem;
    }

    .kelas-tree__node {
        padding: 0.35rem 0;
    }

    .kelas-tree__label {
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
</style>
