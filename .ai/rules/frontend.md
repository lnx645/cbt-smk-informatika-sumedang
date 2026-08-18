---
paths:
  - 'tests/frontend/**'
---

# Frontend

## Frontend tests: Vitest + Testing Library, mock Inertia di setup.ts
Jalankan dengan `bun run test:frontend` (vitest, konfigurasi terpisah di vitest.config.ts dengan `resolve.conditions: ['browser']`). `@inertiajs/svelte` dimock di tests/frontend/setup.ts (router/useForm/usePage/inertia/WhenVisible) — mock form TIDAK reaktif, jadi jangan asersi nilai form via DOM (getByDisplayValue), asersi langsung ke instance form (`useForm.mock.results[1].value.judul`). Modal sveltestrap merender konten walau tertutup → scope query dengan `within()` (`.closest('.modal-content')`). Komponen berat (RichTextEditor/PDF/docx viewers/confirm) dimock via stub di tests/frontend/stubs. Ambil URL wayfinder dari file `resources/js/actions/...` saat asersi (contoh: route siswa adalah `/app/materi`, tanpa prefix `siswa`).
