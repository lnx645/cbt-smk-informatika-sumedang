---
paths:
  - 'resources/js/pages/guru/Materi/**,resources/js/pages/siswa/Materi/**,resources/js/components/RichTextEditor.svelte'
---

# Js Components

## Materi deskripsi is Tiptap HTML — render with {@html}, strip tags for snippets
Materi `deskripsi` stores Tiptap HTML (RichTextEditor.svelte with Bold/Italic/Underline/lists). Render it with `{@html}` where full formatting is wanted, and use the stripHtml() helper for plain-text snippets (e.g. the guru table cell). Controller validates `max:10000` because HTML markup is longer than plain text.
