# Frontend: WayForPay Payment Integration

> task_slug: `wayforpay-payment-integration` | phase: frontend | date: 2026-06-06

---

## Files Created

| File | Purpose |
|------|---------|
| `resources/js/Composables/useMoneyFormat.ts` | `formatMoney(kopiyky, currency): string` — converts kopiyky integers to human-readable currency via `Intl.NumberFormat` |
| `resources/js/Pages/Payments/Success.vue` | Inertia page shown after successful WayForPay redirect; displays order reference, amount, payment system, payable label |
| `resources/js/Pages/Payments/Failure.vue` | Inertia page shown after failed/declined WayForPay redirect; displays failure reason, status badge, links back to bookings |
| `resources/js/Pages/Payments/History.vue` | Paginated payment history list with status/date filters; uses `AppPagination` via computed adapter |

## Files Modified

None. `ShowEditCalendarEvent.vue` was NOT modified — see Blockers.

---

## Key Design Decisions

1. **Pagination adapter instead of inline pagination** — `AppPagination.vue` expects `{links, prev_page_url, next_page_url}`. The backend returns `{data, links, meta}` (custom shape, not raw Laravel paginator). A computed `paginatorData` in `History.vue` maps `links[0].url → prev_page_url` and `links.at(-1).url → next_page_url`, reusing the existing component without modification.

2. **No native form for payment retry** — `POST /payments/initiate` returns raw `text/html` (WayForPay auto-submit form). Inertia's `router.post()` cannot handle this (requires `X-Inertia` JSON response). A native HTML form requires the raw CSRF token from `csrf_token()` — but neither a `<meta name="csrf-token">` tag nor a shared Inertia prop exposes it. The retry button was replaced with a navigation link to the pending bookings list. This is a backend blocker — see below.

3. **`payment` prop is typed as nullable** — both Success and Failure pages render a fallback state when `payment === null` (occurs when `?orderReference=` doesn't match any record). The plan's interfaces omitted `| null`; this was corrected to prevent runtime errors.

4. **`payable.type` casing** — backend emits `class_basename()` output: `"MentorSession"` / `"MentorProgram"` (PascalCase). All type-label mappings use PascalCase keys to match this output correctly.

5. **Layout via template wrapper** — all three pages use `<AuthenticatedLayout>` template wrapper, matching the project convention used in `UserSchedule/ListPage.vue`, `Calendar/*`, etc. (not `defineOptions({ layout })`).

---

## Type Check Status

- `vue-tsc`: not available in node_modules (no `tsconfig.json` in project root — TypeScript is used via ESLint parser only)
- `tsc --noEmit`: no tsconfig found — cannot run
- Vite build: pre-existing esbuild version mismatch (`host 0.27.3 ≠ binary 0.25.9`) — environment issue unrelated to this change
- Manual review: all imports verified correct (`@inertiajs/vue3`, `@heroicons/vue/24/outline`, `@/Composables/*`, `@/Layouts/*`, `@/Components/*`)

---

## Deviations from Plan

| # | Plan stated | Actual | Reason |
|---|-------------|--------|--------|
| 1 | `router.post(route('payments.initiate'))` for pay button | Not implemented — flagged as blocked | `router.post()` cannot handle `text/html` response; no CSRF token available in JS for native form |
| 2 | `ShowEditCalendarEvent.vue` gets pay button | Not modified | `CalendarUIEventData` DTO has no `status`, no `mentor_session_id` — the data contract doesn't support the button without backend changes |
| 3 | `retry_url` → "Спробувати ще раз" button via `router.post` | Replaced with navigation link to pending bookings | Same CSRF/HTML blocker as #1 |
| 4 | `LaravelPaginator<T>` with `prev_page_url`/`next_page_url` | Computed adapter bridges `{data,links,meta}` → `AppPagination` shape | Backend returns custom paginator shape, not raw `->paginate()` output |

---

## Blockers / Open Questions for QA

1. **Payment initiation UI** — `POST /payments/initiate` returns `text/html`. Frontend cannot submit this via Inertia (`router.post`) or native form (no CSRF token exposed). Backend must either:
   - Add `csrf_token()` to `HandleInertiaRequests::share()` as a shared prop, OR
   - Return `Inertia::location()` pointing to a dedicated Blade gateway route that renders the WayForPay HTML (already described in task prompt as "Option C"), OR
   - Expose the payment HTML via a session-flashed GET redirect to a Blade view outside Inertia

2. **Pay button on ShowEditCalendarEvent** — `CalendarUIEventData::toArray()` does not include `status` (no `pending_payment` concept in the DTO), nor `mentor_session_id`. Backend must extend the DTO and the action to expose these before the frontend button can be wired.

3. **`pages.calendar.pending` route** — used in `Failure.vue` "Повернутись до бронювань" link. Confirmed exists in `routes/web.php` as `GET /calendar/pending-calendar-event/list/{mentorProgram:slug?}` — no params required.

4. **`flash` TypeScript type** — `usePage().props.flash` is typed as `unknown` since no `PageProps` interface is declared globally. Cast to `{ success?: string; error?: string }` is used locally in each page. A global `PageProps` interface could be added to `resources/js/app.js` or a `types/inertia.d.ts` to reduce repetition — tracked as future improvement.
