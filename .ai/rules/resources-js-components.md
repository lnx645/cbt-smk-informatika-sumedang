---
paths:
  - resources/js/components/RichTextEditor.svelte
---

# Resources Js Components

## Fitur RichTextEditor: daftar isi, rumus, YouTube, audio
RichTextEditor (Tiptap v3, manual Svelte 5 integration) menyimpan konten sebagai HTML di kolom `konten`/`deskripsi` materi. Fitur: heading ber-id (`HeadingWithId`), daftar isi (`DaftarIsi` node + `syncDaftarIsi` di onTransaction), rumus LaTeX (`@tiptap/extension-mathematics` + `katex`, CSS wajib `katex/dist/katex.min.css` di editor DAN halaman render), video YouTube (`@tiptap/extension-youtube`, command `setYoutubeVideo`), audio (`AudioPlayer` node kustom), link (`@tiptap/extension-link`). Halaman siswa render pakai `{@html}` — heading punya `scroll-margin-top` agar tidak tertutup navbar. Daftar isi hanya muncul jika ada heading berisi teks.
