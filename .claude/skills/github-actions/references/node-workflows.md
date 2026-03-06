# Node.js / TypeScript / JavaScript Workflow Patterns

## Setup Node.js

```yaml
- uses: actions/setup-node@v4
  with:
    node-version: '22'
    cache: 'npm'
```

### With .nvmrc

```yaml
- uses: actions/setup-node@v4
  with:
    node-version-file: '.nvmrc'
    cache: 'npm'
```

## Basic CI

```yaml
jobs:
  ci:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '22'
          cache: 'npm'
      - run: npm ci
      - run: npm run lint
      - run: npm run type-check
      - run: npm test
      - run: npm run build
```

## Lint and Format

### ESLint

```yaml
lint:
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
    - uses: actions/setup-node@v4
      with:
        node-version: '22'
        cache: 'npm'
    - run: npm ci
    - run: npx eslint . --max-warnings=0
```

### Prettier

```yaml
    - run: npx prettier --check .
```

### TypeScript Type Check

```yaml
type-check:
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
    - uses: actions/setup-node@v4
      with:
        node-version: '22'
        cache: 'npm'
    - run: npm ci
    - run: npx tsc --noEmit
```

## Testing

### Vitest

```yaml
test:
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
    - uses: actions/setup-node@v4
      with:
        node-version: '22'
        cache: 'npm'
    - run: npm ci
    - run: npx vitest run --coverage
    - uses: codecov/codecov-action@v4
      with:
        token: ${{ secrets.CODECOV_TOKEN }}
      if: always()
```

### Jest

```yaml
    - run: npx jest --ci --coverage --forceExit
```

### Playwright E2E

```yaml
e2e:
  runs-on: ubuntu-latest
  needs: [build]
  steps:
    - uses: actions/checkout@v4
    - uses: actions/setup-node@v4
      with:
        node-version: '22'
        cache: 'npm'
    - run: npm ci
    - run: npx playwright install --with-deps chromium
    - run: npx playwright test
    - uses: actions/upload-artifact@v4
      if: failure()
      with:
        name: playwright-report
        path: playwright-report/
```

## Build and Deploy

### Static Site / SPA Build

```yaml
build:
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
    - uses: actions/setup-node@v4
      with:
        node-version: '22'
        cache: 'npm'
    - run: npm ci
    - run: npm run build
    - uses: actions/upload-artifact@v4
      with:
        name: dist
        path: dist/
```

### npm Package Publish

```yaml
publish:
  runs-on: ubuntu-latest
  needs: [ci]
  if: startsWith(github.ref, 'refs/tags/v')
  permissions:
    id-token: write
  steps:
    - uses: actions/checkout@v4
    - uses: actions/setup-node@v4
      with:
        node-version: '22'
        registry-url: 'https://registry.npmjs.org'
    - run: npm ci
    - run: npm publish --provenance --access public
      env:
        NODE_AUTH_TOKEN: ${{ secrets.NPM_TOKEN }}
```

## Matrix Testing

```yaml
strategy:
  fail-fast: true
  matrix:
    node: ['20', '22']
    os: [ubuntu-latest, macos-latest]
runs-on: ${{ matrix.os }}
steps:
  - uses: actions/setup-node@v4
    with:
      node-version: ${{ matrix.node }}
      cache: 'npm'
  - run: npm ci
  - run: npm test
```

## Monorepo with Turborepo

```yaml
    - run: npm ci
    - run: npx turbo run lint test build --filter=...[HEAD~1]
```

## Recommended Job Pipeline (fail fast)

1. **Lint + Prettier** — format/style (~15s)
2. **Type check** — TypeScript compilation (~20s)
3. **Unit tests** — Vitest/Jest (~30s)
4. **Build** — production build (~45s)
5. **E2E tests** — Playwright (slowest, depends on build)