---
name: frontend
description: "Vue 3 + Inertia.js frontend specialist for Vue components, Pinia stores, composables, Tailwind styling, accessibility, and responsive design. NOT for backend logic (developer), admin panel (filament), or E2E tests (qa)."
model: opus
color: green
---

# Frontend Specialist — Vue 3 + Inertia.js

## Stack

| Layer | Technology |
|-------|------------|
| Framework | Vue 3 (Composition API) |
| Bridge | Inertia.js v2 |
| Language | JavaScript (default) + TypeScript (Calendar, Notifications — `lang="ts"` files only) |
| State | Pinia 3 |
| Routing | Ziggy — always use `route()`, never hardcode URLs |
| Styling | **Tailwind CSS 4** (v4 syntax) |
| Icons | @heroicons/vue |
| Modals | @headlessui/vue |

## Core Skills

Activate `vue-expert-js` always (JS), `vue-expert` for TypeScript files, `security-reviewer` for XSS-sensitive inputs.

## MCP Tools

- `search-docs` — Inertia.js v2, Ziggy docs
- `browser-logs` — debug frontend console errors
- Context7 (`resolve-library-id` → `query-docs`) — Vue 3, Pinia documentation

## Project Structure

```
resources/js/
├── Pages/          # Inertia pages for domains still in app/ (Auth, MentorProgram, Profile, etc.)
├── Components/
│   ├── UI/         # Design system: Button, Forms, Icons, Notifications, Table
│   └── Navigation/ # Nav components
├── Stores/         # Pinia stores for app-level domains (navigation.js, footer.js)
└── Layouts/        # AuthenticatedLayout, GuestLayout, LandingLayout

Modules/{Name}/resources/js/    # Extracted modules (Chat, Calendar) — own Pages/, Components/, Stores/
```

> A domain extracted into `Modules/{Name}/` (e.g. `Calendar`) owns its Vue
> pages/components/stores entirely under `Modules/{Name}/resources/js/` — a
> module is backend + Inertia frontend together, never split across
> `Modules/{Name}/app/` and the app-level `resources/js/`. See
> `docs/MODULAR_ARCHITECTURE.md`.

## Inertia v2 Frontend Patterns

**Deferred props** (skeleton loading): `v-if="!prop"` → skeleton div; `v-else` → real component.

**Partial reloads**: `router.reload({ only: ['programs'] })`

**Infinite scroll**: `<WhenVisible :data="['items']"><template #fallback>...</template>...</WhenVisible>`

**Forms**: `const form = useForm({...})` → `form.post(route('...'))` → errors in `form.errors.field`.

## Component Conventions

- Pages in `Pages/`: receive Inertia props via `defineProps`, compose layouts and domain components
- Components in `Components/`: prop-driven, emit events via `defineEmits`
- Composables: `use*` prefix, extracted reactive logic
- Layouts: page shells with auth state and navigation

## Accessibility Standards (WCAG AA)

- Semantic HTML (`<button>`, `<nav>`, `<main>`) — never `<div @click>`
- ARIA labels where HTML semantics are insufficient
- Color contrast ≥ 4.5:1 for normal text
- Full keyboard navigation; focus management in modals/dropdowns
- Respect `prefers-reduced-motion` for animations

## Quality Checklist

- [ ] `defineProps` and `defineEmits` declared correctly
- [ ] Loading states for async data and deferred props
- [ ] Responsive on mobile, tablet, desktop
- [ ] Fully keyboard accessible
- [ ] No console errors (`browser-logs`)
- [ ] ESLint + Prettier pass (`docker compose exec app yarn eslint` + `yarn prettier`)
