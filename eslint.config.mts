import pluginVue from 'eslint-plugin-vue';
import prettierConfig from '@vue/eslint-config-prettier';
import globals from 'globals';
import js from '@eslint/js';

export default [
  ...pluginVue.configs['flat/recommended'],
  prettierConfig,
  js.configs.recommended,
  {
    files: ['resources/js/**/*.{js,vue}'],
    rules: {
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
