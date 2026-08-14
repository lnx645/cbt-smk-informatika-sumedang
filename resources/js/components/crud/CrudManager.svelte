<script lang="ts">
    import type { Component, Snippet } from 'svelte';
    import { useForm, router } from '@inertiajs/svelte';
    import {
        Button,
        Card,
        CardBody,
        Table,
        Modal,
        ModalHeader,
        ModalBody,
        ModalFooter,
        FormGroup,
        Input,
        Label,
        Badge,
    } from '@sveltestrap/sveltestrap';
    import AppHead from '@/components/AppHead.svelte';
    import Select from '@/components/Select.svelte';
    import { confirm } from '@/lib/confirm.svelte';
    import type { RouteDefinition } from '@/wayfinder';

    type CrudItem = Record<string, unknown> & { id: number };

    export type CrudColumn = {
        key: string;
        label: string;
        center?: boolean;
        badge?: boolean;
        badgeColor?: string;
        format?: (value: unknown, item: CrudItem) => string;
        cell?: Snippet<[CrudItem]>;
        cellComponent?: Component<{ item: CrudItem }>;
    };

    export type CrudFieldOption = { value: string | number; label: string };

    export type CrudAction = {
        key: string;
        label?: string;
        icon?: string;
        color?: string;
        class?: string;
        onClick: (item: CrudItem) => void;
    };

    export type CrudField = {
        name: string;
        label: string;
        type?:
            | 'text'
            | 'email'
            | 'number'
            | 'textarea'
            | 'select'
            | 'date'
            | 'file'
            | 'image';
        placeholder?: string;
        options?: CrudFieldOption[];
        accept?: string;
        required?: boolean;
        editable?: boolean;
        multiple?: boolean;
    };

    type AnyRoute = RouteDefinition<'get' | 'post' | 'put' | 'delete'>;

    type CrudController = {
        store: (options?: any) => AnyRoute;
        update: (args: any) => AnyRoute;
        destroy: (args: any) => AnyRoute;
    };

    let {
        title = '',
        subtitle = '',
        columns = [],
        fields = [],
        items = [],
        controller,
        resourceName,
        createLabel = 'Tambah',
        emptyText = 'Belum ada data.',
        actions = [],
    }: {
        title?: string;
        subtitle?: string;
        columns?: CrudColumn[];
        fields?: CrudField[];
        items?: CrudItem[];
        controller: CrudController;
        resourceName?: string;
        createLabel?: string;
        emptyText?: string;
        actions?: CrudAction[];
    } = $props();

    const label = $derived(resourceName ?? title ?? 'data');

    let modalOpen = $state(false);
    let editing = $state<CrudItem | null>(null);
    let previews = $state<Record<string, string>>({});

    const form = useForm(
        Object.fromEntries(
            fields.map((f) => [f.name, f.multiple ? [] : '']),
        ),
    );

    function display(col: CrudColumn, item: CrudItem): string {
        const value = item[col.key];
        return col.format ? col.format(value, item) : String(value ?? '');
    }

    function fieldValue<T = unknown>(name: string): T {
        return (form as Record<string, unknown>)[name] as T;
    }

    function setFile(name: string, file: File | null) {
        (form as Record<string, unknown>)[name] = file;
        if (previews[name]) {
            URL.revokeObjectURL(previews[name]);
        }
        previews[name] = file ? URL.createObjectURL(file) : '';
    }

    function openCreate() {
        editing = null;
        form.reset();
        previews = {};
        modalOpen = true;
    }

    function openEdit(item: CrudItem) {
        editing = item;
        form.reset();
        previews = {};
        for (const field of fields) {
            (form as Record<string, unknown>)[field.name] =
                item[field.name] ?? '';
        }
        modalOpen = true;
    }

    function submit() {
        if (editing) {
            const route = controller.update(editing);
            form.submit({ url: route.url, method: route.method });
        } else {
            const route = controller.store();
            form.submit({ url: route.url, method: route.method });
        }
    }

    async function confirmDelete(item: CrudItem) {
        const ok = await confirm.show({
            title: `Hapus ${label}?`,
            message: `${String(item.name ?? item.id)} akan dihapus secara permanen.`,
        });

        if (!ok) {
            return;
        }

        router.delete(controller.destroy(item).url);
    }
</script>

<AppHead title={title} />

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h1 class="h4 mb-1 fw-bold">{title}</h1>
        {#if subtitle}
            <p class="text-muted mb-0 small">{subtitle}</p>
        {/if}
    </div>
    <Button color="primary" onclick={openCreate}>
        <i class="bi bi-plus-lg me-1"></i> {createLabel}
    </Button>
</div>

<Card class="border-0 shadow-sm">
    <CardBody class="p-0">
        <Table hover responsive class="mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    {#each columns as col (col.key)}
                        <th class={col.center ? 'text-center' : ''}>{col.label}</th>
                    {/each}
                    <th class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                {#each items as item (item.id)}
                    <tr>
                        {#each columns as col (col.key)}
                            <td class={col.center ? 'text-center' : ''}>
                                {#if col.cell}
                                    {@render col.cell(item)}
                                {:else if col.cellComponent}
                                    <col.cellComponent item={item} />
                                {:else if col.badge}
                                    <Badge color={col.badgeColor ?? 'info'}>
                                        {display(col, item)}
                                    </Badge>
                                {:else}
                                    {display(col, item)}
                                {/if}
                            </td>
                        {/each}
                        <td class="text-end pe-3">
                            {#each actions as act (act.key)}
                                <Button
                                    size="sm"
                                    color={act.color ?? 'light'}
                                    class={act.class ?? 'me-1'}
                                    title={act.label}
                                    onclick={() => act.onClick(item)}
                                >
                                    {#if act.icon}
                                        <i class={act.icon}></i>
                                    {/if}
                                    {#if act.label}
                                        {act.label}
                                    {/if}
                                </Button>
                            {/each}
                            <Button
                                size="sm"
                                color="light"
                                class="me-1"
                                onclick={() => openEdit(item)}
                            >
                                <i class="bi bi-pencil"></i>
                            </Button>
                            <Button
                                size="sm"
                                color="light"
                                class="text-danger"
                                onclick={() => confirmDelete(item)}
                            >
                                <i class="bi bi-trash"></i>
                            </Button>
                        </td>
                    </tr>
                {:else}
                    <tr>
                        <td colspan={columns.length + 1} class="text-center text-muted py-4">
                            {emptyText}
                        </td>
                    </tr>
                {/each}
            </tbody>
        </Table>
    </CardBody>
</Card>

<Modal isOpen={modalOpen} toggle={() => (modalOpen = !modalOpen)}>
    <ModalHeader toggle={() => (modalOpen = !modalOpen)}>
        {editing ? `Edit ${label}` : `Tambah ${label}`}
    </ModalHeader>
    <form onsubmit={(e) => { e.preventDefault(); submit(); }}>
        <ModalBody>
            {#each fields as field, i (field.name)}
                <FormGroup class={i === fields.length - 1 ? 'mb-0' : ''}>
                    <Label for={field.name} class="small fw-semibold">
                        {field.label}
                    </Label>

                    {@const locked = !!editing && field.editable === false}

                    {#if field.type === 'select'}
                        <Select
                            items={field.options ?? []}
                            value={(form as Record<string, unknown>)[field.name]}
                            multiple={field.multiple ?? false}
                            disabled={locked}
                            hasError={!!(form.errors as Record<string, string>)[field.name]}
                            placeholder={field.placeholder ?? 'Pilih…'}
                            getOptionValue={(item: unknown) =>
                                (item as CrudFieldOption).value}
                            onchange={(v: unknown) =>
                                ((form as Record<string, unknown>)[field.name] = v)}
                        />
                    {:else if field.type === 'textarea'}
                        <Input
                            id={field.name}
                            type="textarea"
                            bind:value={(form as Record<string, unknown>)[field.name]}
                            disabled={locked}
                            invalid={!!(form.errors as Record<string, string>)[field.name]}
                            placeholder={field.placeholder ?? ''}
                        />
                    {:else if field.type === 'file' || field.type === 'image'}
                        <Input
                            id={field.name}
                            type="file"
                            accept={field.accept ?? (field.type === 'image' ? 'image/*' : undefined)}
                            disabled={locked}
                            onchange={(e: Event) =>
                                setFile(
                                    field.name,
                                    (e.currentTarget as HTMLInputElement).files?.[0] ?? null,
                                )}
                            invalid={!!(form.errors as Record<string, string>)[field.name]}
                        />
                        {#if fieldValue<string>(field.name)}
                            <div class="mt-2">
                                <img
                                    src={fieldValue<File>(field.name)
                                        ? previews[field.name]
                                        : fieldValue<string>(field.name)}
                                    alt={field.label}
                                    class="img-thumbnail"
                                    style="max-height: 96px;"
                                />
                            </div>
                        {/if}
                    {:else}
                        <Input
                            id={field.name}
                            type={(field.type ?? 'text') as never}
                            bind:value={(form as Record<string, unknown>)[field.name]}
                            disabled={locked}
                            invalid={!!(form.errors as Record<string, string>)[field.name]}
                            placeholder={field.placeholder ?? ''}
                        />
                    {/if}

                    {#if (form.errors as Record<string, string>)[field.name]}
                        <div class="text-danger small mt-1">
                            {(form.errors as Record<string, string>)[field.name]}
                        </div>
                    {/if}
                </FormGroup>
            {/each}
        </ModalBody>
        <ModalFooter>
            <Button color="secondary" outline onclick={() => (modalOpen = false)}>
                Batal
            </Button>
            <Button color="primary" type="submit" disabled={form.processing}>
                {editing ? 'Simpan Perubahan' : 'Simpan'}
            </Button>
        </ModalFooter>
    </form>
</Modal>
