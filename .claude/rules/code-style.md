# PHP & Eloquent Code Style

See `docs/NAMING_CONVENTIONS.md` for the full reference.

## Strict PHP

- All PHP files must declare `declare(strict_types=1)`
- Full type hints required for all parameters and return types
- Use `===` not `==`; PHP 8.4 features; trailing commas in multiline

## Eloquent Conventions

- **Never** access `$model->id` directly — use `$model->getKey()`
- Use `query()` method for model queries (not static `Model::where(...)`)
- Prefer eager loading (`with()`, `withCount()`), scopes, pagination, soft deletes

## Code Quality Tools

| Tool | Purpose |
|------|---------|
| Laravel Pint | Formatting (Laravel preset, strict) |
| PHPStan Level 7 | Static analysis (Larastan) |
| Rector | PHP 8.4 + Laravel 12 modernization |
| Cognitive Complexity | Class ≤85, function ≤8 |
