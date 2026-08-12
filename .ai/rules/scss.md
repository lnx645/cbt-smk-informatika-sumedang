---
paths:
  - 'resources/scss/**'
---

# Scss

## SCSS theme is the source of truth; app.css is committed compiled output
Color theme is driven entirely by resources/scss/_tokens.scss (education blue palette: $blue-* primary, $slate-* neutrals, $sky-* accent). resources/css/app.css is the committed compiled artifact — vite does NOT compile scss, so after editing tokens/components run: `bunx sass ./resources/scss/input.scss resources/css/app.css` (or `bun run scss:run`) and then `bun run build`.
