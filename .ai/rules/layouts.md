---
paths:
  - resources/js/layouts/AppShellLayout.svelte
---

# Layouts

## Sidebar collapse state is persisted in localStorage
AppShellLayout supports a desktop-only mini sidebar (`sidebarCollapsed` → `.collapsed`, ~76px, icons-only, text hidden via media (min-width:992px)). Preference is persisted to `localStorage` key `app-sidebar-collapsed`. On click the chevron toggle flips this state (mini 76px icons-only ↔ full 280px). All `.collapsed` visuals live under `@media (min-width: 992px)` so mobile is unaffected. Hover-to-expand is **chevron-driven only** (not the whole sidebar): hovering the collapse-toggle (or, once expanded, moving into the sidebar) sets a temporary `hoverExpand` (via `sidebarMini = collapsed && !hoverExpand`) that reveals the full sidebar for ~180ms so labels are readable, then collapses back to the persisted mini state. Hover handlers are gated by an `isDesktop` media query so they never fire on touch. On mobile the hamburger opens an overlay drawer and `navigate()` closes it whenever a nav link (including the header "Beranda") is clicked.
