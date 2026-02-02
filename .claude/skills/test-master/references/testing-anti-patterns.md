# Testing Anti-Patterns

> Reference for: Test Master
> Load when: test review, mock issues, test quality problems

## Core Principle

> **"Test what the code does, not what the mocks do."**

## The Five Anti-Patterns

### Anti-Pattern 1: Testing Mock Behavior

```typescript
// ❌ BAD: Testing the mock, not the behavior
it('should call the API', () => {
  const mockApi = jest.fn().mockResolvedValue({ data: 'test' });
  service.getUser(1);
  expect(mockApi).toHaveBeenCalledWith(1); // Testing mock, not result
});

// ✅ GOOD: Testing actual behavior
it('should return user data from API', async () => {
  const mockApi = jest.fn().mockResolvedValue({ id: 1, name: 'Alice' });
  const user = await service.getUser(1);
  expect(user.name).toBe('Alice'); // Testing actual output
});
```

### Anti-Pattern 2: Test-Only Methods in Production

```typescript
// ❌ BAD: Production code polluted with test concerns
class UserCache {
  _resetForTesting(): void { this.cache.clear(); }
}

// ✅ GOOD: Use fresh instances per test instead
function createFreshCache(): UserCache {
  return new UserCache();
}
```

### Anti-Pattern 3: Over-Mocking

```typescript
// ❌ BAD: Mocking everything
jest.mock('./inventory');
jest.mock('./payment');
jest.mock('./shipping');
// What did we actually test?

// ✅ GOOD: Strategic mocking
const inventory = new InventoryService(testDb); // Real
const payment = mockPaymentGateway(); // Mock only external
```

### Anti-Pattern 4: Incomplete Mocks

```typescript
// ❌ BAD: Incomplete mock
mockResolvedValue({ id: 1, name: 'Test' });
// Missing: email, createdAt, permissions...

// ✅ GOOD: Use factories
mockResolvedValue(createMockUser({ name: 'Test' }));
```

### Anti-Pattern 5: Tests as Afterthought

```typescript
// ❌ BAD: "We'll add tests later" (Day 30: catastrophic bug)

// ✅ GOOD: Tests ship with feature
it('should reject duplicate usernames', async () => {
  await createUser({ username: 'alice' });
  await expect(createUser({ username: 'alice' }))
    .rejects.toThrow('Username already exists');
});
```

## Detection Checklist

| Warning Sign | Anti-Pattern |
|-------------|--------------|
| `expect(mock).toHaveBeenCalled()` only | Testing mock behavior |
| Methods with `_ForTesting` | Test-only methods |
| Every dependency mocked | Over-mocking |
| Mocks return minimal stubs | Incomplete mocks |
| Tests added weeks after feature | Tests as afterthought |

---
*Adapted from [obra/superpowers](https://github.com/obra/superpowers) by Jesse Vincent (@obra), MIT License.*
