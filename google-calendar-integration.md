# Google Calendar Integration

## Goal
Allow authenticated users to connect Google Calendar via OAuth, storing tokens in a new `user_calendar_integrations` table, using a provider interface that future Outlook/Apple providers will implement.

## Tasks

- [ ] 1. Migration: create `user_calendar_integrations` table → Verify: `php artisan migrate` runs clean
- [ ] 2. Model: `UserCalendarIntegration` with encrypted casts for tokens, `CalendarProviderEnum` → Verify: model file exists, casts defined
- [ ] 3. Interface: `CalendarProviderInterface` with `redirect()` and `handleCallback(User)` → Verify: file exists in `App\Contracts\Calendar`
- [ ] 4. Provider: `GoogleCalendarProvider` implements interface — Socialite with calendar scope, offline access → Verify: class compiles, PHPStan passes
- [ ] 5. Actions: `CalendarConnectRedirect` + `CalendarConnectCallback` — resolve provider from container by driver string → Verify: classes compile
- [ ] 6. Routes + ServiceProvider binding: `calendar.connect.redirect`, `calendar.connect.callback` under `auth` middleware → Verify: `php artisan route:list` shows routes
- [ ] 7. Settings page: Vue component with Connect button + status badge, Inertia props → Verify: page renders, button triggers redirect
- [ ] 8. Tests: feature tests for redirect, callback (token storage), disconnect → Verify: `php artisan test --filter=GoogleCalendar` passes
- [ ] 9. Pint + PHPStan + Rector → Verify: all pass clean

## Done When
- [ ] User clicks "Connect Google Calendar", completes OAuth, row appears in `user_calendar_integrations`
- [ ] Settings page reflects connected status
- [ ] All tests green, static analysis clean
