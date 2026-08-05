import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { createApp, h } from 'vue';

import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { resolvePage } from './resolvePage.js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: resolvePage,
  setup({ el, App, props, plugin }) {
    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(createPinia())
      .use(ZiggyVue)
      .mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});
