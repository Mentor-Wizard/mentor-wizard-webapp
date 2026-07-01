# Testing Rules

See `docs/TESTING_STRATEGY.md` for full strategy and examples.

## Models

**DO NOT** unit-test basic Eloquent models (CRUD, relationships, factories). Test model behaviour via feature/integration tests instead.

## Framework

- **Pest PHP** — `describe()` + `it()` + `expect()`; `mutates(Class::class)` on all Unit tests
- **Mutation Testing** — `--mutate --covered-only --parallel --min=100` (Unit tests only)
  - Not required for Enum classes that are pure value/label mappings with no conditional business logic (e.g. `names()`/`values()` wrappers); keep `mutates()` only on Enums with real branching (delegation, grouped `match`, bounds/loop logic).
- **Arch Testing** — `tests/Unit/ArchTest.php` (strict_types, Models extend Eloquent, Page suffix, Enums)

## Structure

```
tests/
├── Feature/   # HTTP endpoints, workflows (RefreshDatabase)
└── Unit/      # Actions, Observers, Support — must mutate
```

## Running Tests (Docker)

```bash
docker compose exec app php artisan test                                          # all
docker compose exec app php artisan test --mutate --covered-only --parallel --min=100  # mutation
```
