# Quick Fixes

> Reference for: Debugging Wizard
> Load when: Fixing common errors

## TypeError: Cannot read property 'x' of undefined

```typescript
// Error
user.profile.name
// user or profile is undefined

// Fix: Optional chaining
user?.profile?.name

// Fix: Default value
user?.profile?.name ?? 'Unknown'

// Fix: Guard clause
if (!user?.profile) {
  return null;
}
return user.profile.name;
```

## Unhandled Promise Rejection

```typescript
// Error
fetchData().then(process);
// What if fetchData rejects?

// Fix: Add catch
fetchData()
  .then(process)
  .catch(error => {
    console.error('Fetch failed:', error);
  });

// Fix: try/catch with await
try {
  const data = await fetchData();
  await process(data);
} catch (error) {
  console.error('Operation failed:', error);
}
```

## Maximum Call Stack Size Exceeded

```typescript
// Error: Infinite recursion
function factorial(n) {
  return n * factorial(n - 1); // No base case!
}

// Fix: Add base case
function factorial(n) {
  if (n <= 1) return 1;
  return n * factorial(n - 1);
}
```

## Module Not Found

```bash
# Error: Cannot find module 'x'

# Fix 1: Install the package
npm install x

# Fix 2: Check import path
import x from './x';     # Relative - needs ./
import x from 'x';       # Package - no ./

# Fix 3: Clear cache
rm -rf node_modules package-lock.json
npm install
```

## Async/Await Issues

```typescript
// Error: await in non-async function
function getData() {
  const data = await fetch('/api'); // SyntaxError!
}

// Fix: Mark function as async
async function getData() {
  const data = await fetch('/api');
}

// Error: forEach doesn't await
items.forEach(async item => {
  await process(item); // Doesn't wait!
});

// Fix: Use for...of
for (const item of items) {
  await process(item);
}

// Fix: Use Promise.all for parallel
await Promise.all(items.map(item => process(item)));
```

## PHP / Laravel Common Errors

```php
// Error: Class not found
// Fix: Run composer dump-autoload
composer dump-autoload

// Error: Method not found on model
// Fix: Check relationship name, run ide-helper
php artisan ide-helper:models

// Error: SQLSTATE[HY000] Connection refused
// Fix: Check database config in .env
DB_HOST=127.0.0.1
DB_PORT=5432
```

## Quick Reference

| Error Message | Likely Fix |
|--------------|------------|
| Cannot read property of undefined | Optional chaining `?.` |
| Unhandled promise rejection | Add `.catch()` or try/catch |
| Maximum call stack | Add recursion base case |
| Module not found | Check path, install package |
| await in non-async | Add `async` keyword |
| Class not found | `composer dump-autoload` |
