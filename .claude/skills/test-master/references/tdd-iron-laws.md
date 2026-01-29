# TDD Iron Laws

> Reference for: Test Master
> Load when: TDD methodology, test-first development, red-green-refactor

## The Fundamental Principle

> **NO PRODUCTION CODE WITHOUT A FAILING TEST FIRST.**

## The Three Iron Laws

### Iron Law 1: The Fundamental Rule
> "You shall not write any production code unless it is to make a failing test pass."

### Iron Law 2: Proof Through Observation
> "If you didn't watch the test fail, you don't know if it tests the right thing."

### Iron Law 3: The Final Rule
> "Production code exists → A test exists that failed first. Otherwise → It's not TDD."

## The RED-GREEN-REFACTOR Cycle

### RED: Write One Minimal Failing Test
```typescript
it('should return 0 for empty array', () => {
  expect(sum([])).toBe(0);
});
// Run: ✗ FAIL - sum is not defined
```

### GREEN: Implement Simplest Passing Code
```typescript
function sum(numbers: number[]): number {
  return 0;
}
// Run: ✓ PASS
```

### REFACTOR: Improve While Keeping Tests Green
```typescript
function sum(numbers: number[]): number {
  return numbers.reduce((acc, n) => acc + n, 0);
}
// Run: ✓ PASS (still)
```

## Verification Checklist

- [ ] Every production function has corresponding tests
- [ ] Each test was written before its implementation
- [ ] Each test was observed to fail first
- [ ] Tests verify behavior, not implementation
- [ ] Refactoring kept all tests green

---
*Adapted from [obra/superpowers](https://github.com/obra/superpowers) by Jesse Vincent (@obra), MIT License.*
