# QA Methodology

> Reference for: Test Master
> Load when: Test strategy, manual testing, quality advocacy

## Manual Testing Types

### Exploratory Testing
```markdown
**Charter**: Explore {feature} with focus on {aspect}
**Duration**: 60-90 min
**Mission**: Find defects in {specific functionality}

Test Ideas:
- Boundary conditions & edge cases
- Error handling & recovery
- User workflow variations
```

### Accessibility Testing (WCAG 2.1 AA)
```typescript
test('accessibility compliance', async ({ page }) => {
  await page.keyboard.press('Tab');
  expect(['A', 'BUTTON', 'INPUT']).toContain(
    await page.evaluate(() => document.activeElement.tagName)
  );
});
```

## Quality Metrics

```typescript
// Defect Removal Efficiency (target: >95%)
const dre = (defectsInTesting / (defectsInTesting + defectsInProd)) * 100;

// Defect Leakage (target: <5%)
const leakage = (defectsInProd / totalDefects) * 100;
```

## Quality Gates

```markdown
**Must Pass (Blockers)**:
- [ ] Zero critical defects
- [ ] Coverage >80%
- [ ] All P0/P1 tests passing
- [ ] Performance SLA met
- [ ] Security scan clean
```

## Quick Reference

| Metric | Excellent | Good | Needs Work |
|--------|-----------|------|------------|
| Coverage | >90% | 70-90% | <70% |
| Leakage | <2% | 2-5% | >5% |
| Automation | >80% | 60-80% | <60% |
