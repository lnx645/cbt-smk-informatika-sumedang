---
paths:
  - 'resources/scss/**'
  - resources/scss/_tokens.scss
---

# Scss

## SCSS theme is the source of truth; app.css is committed compiled output
Color theme is driven entirely by resources/scss/_tokens.scss (brand blue palette: $blue-* primary #4182b3, $slate-* neutrals, $sky-* accent). resources/css/app.css is the committed compiled artifact — vite does NOT compile scss, so after editing tokens/components run: `bunx sass ./resources/scss/input.scss resources/css/app.css` (or `bun run scss:run`) and then `bun run build`.

## Brand blue palette menggantikan Clay terracotta
Primary scale kini brand blue: $blue-500 = #4182b3 (primary), $blue-700 = #2b567a (hover/teks di atas putih), ramp lengkap $blue-50 s/d $blue-950. Jangan kembalikan nilai Clay #D97757/#C6613F. Kompilasi: bun run scss:run lalu bun run build.
