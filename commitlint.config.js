export default {
  extends: ['@commitlint/config-conventional'],
  rules: {
    // Soften the rules while keeping conventional prefixes
    'type-enum': [
      2,
      'always',
      [
        'feat',
        'fix',
        'docs',
        'style',
        'refactor',
        'perf',
        'test',
        'build',
        'ci',
        'chore',
        'revert',
      ],
    ], // Keep conventional commit types
    'subject-case': [0], // Allow any case for subject
    'subject-max-length': [0], // Remove subject length limit
    'body-max-line-length': [0], // Remove body line length limit
    'footer-max-line-length': [0], // Remove footer line length limit
    'header-max-length': [0], // Remove header length limit
    'scope-case': [0], // Allow any case for scope
    'subject-empty': [2, 'never'], // Still require non-empty subject
    'type-empty': [2, 'never'], // Still require type
    'subject-full-stop': [0], // Allow full stops in subject
    'body-leading-blank': [0], // Don't require blank line before body
    'footer-leading-blank': [0], // Don't require blank line before footer
  },
};
