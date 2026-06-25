import js from '@eslint/js';
import globals from 'globals';

export default [
  js.configs.recommended,
  {
    files: ['assets/js/**/*.js'],
    languageOptions: {
      ecmaVersion: 2022,
      globals: {
        ...globals.browser,
        L: 'readonly',
      },
    },
    rules: {
      'no-unused-vars': 'error',
      'no-undef': 'error',
      'eqeqeq': ['error', 'always', { null: 'ignore' }],
      'no-var': 'error',
    },
  },
];
