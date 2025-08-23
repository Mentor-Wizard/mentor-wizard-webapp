import vue from 'eslint-plugin-vue';
import prettierConfig from '@vue/eslint-config-prettier';

export default [
    ...vue.configs['flat/recommended'],
    prettierConfig,
    {
        files: ['resources/js/**/*.{js,vue}'],
    }
];
