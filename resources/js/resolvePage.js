import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

/**
 * Resolves an Inertia page name to its component, looking in module-scoped
 * page directories before falling back to the app-level one.
 *
 * A name like `Calendar/ShowEditCalendarEvent` is first tried as
 * `Modules/Calendar/resources/js/Pages/Calendar/ShowEditCalendarEvent.vue`,
 * then as `resources/js/Pages/Calendar/ShowEditCalendarEvent.vue`.
 *
 * Shared by `app.js` (client) and `ssr.js` (server) so both resolve identically.
 */
export function resolvePage(name) {
  const appPages = import.meta.glob('./Pages/**/*.vue');
  const modulePages = import.meta.glob(
    '../../Modules/*/resources/js/Pages/**/*.vue',
  );

  const [moduleName, ...rest] = name.split('/');
  const candidates =
    rest.length ?
      [
        `../../Modules/${moduleName}/resources/js/Pages/${name}.vue`,
        `./Pages/${name}.vue`,
      ]
    : [`./Pages/${name}.vue`];

  return resolvePageComponent(candidates, { ...modulePages, ...appPages });
}
