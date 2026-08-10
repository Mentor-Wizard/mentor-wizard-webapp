import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = path.dirname(fileURLToPath(import.meta.url));

const devServerUrl =
  process.env.DDEV_PRIMARY_URL ?
    `${process.env.DDEV_PRIMARY_URL.replace(/:\d+$/, '')}:5173`
  : process.env.VITE_DEV_SERVER_URL || 'http://localhost:5173';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/js/app.js', 'resources/css/minimal.css'],
      ssr: 'resources/js/ssr.js',
      refresh: true,
      valetTls: false,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@modules': path.resolve(projectRoot, 'Modules'),
    },
  },
  server: {
    https: false,
    host: '0.0.0.0',
    port: 5173,
    origin: devServerUrl,
    cors: true,
  },
});
