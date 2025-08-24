import pluginVue from 'eslint-plugin-vue';
import prettierConfig from '@vue/eslint-config-prettier';
import globals from 'globals';
import js from '@eslint/js';
import oxlint from 'eslint-plugin-oxlint';
import simpleImportSort from "eslint-plugin-simple-import-sort";

export default [
  ...pluginVue.configs['flat/recommended'],
  ...oxlint.configs['flat/recommended'],
  js.configs.recommended,
  prettierConfig,
  {
    files: ['resources/js/**/*.{js,vue}'],
    plugins: {
      "simple-import-sort": simpleImportSort,
    },
    rules: {
      "simple-import-sort/imports": "error",
      "simple-import-sort/exports": "error",
      'vue/block-order': [
        'error',
        {
          order: ['script', 'template', 'style'],
        },
      ],
    },
    languageOptions: {
      globals: {
        ...globals.browser,
        route: true,
      },
    },
  },
];
