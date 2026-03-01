# Test Reports

> Reference for: Test Master
> Load when: Creating test reports, documenting findings

## Test Report Template

```markdown
# Test Report: {Feature Name}

**Date**: YYYY-MM-DD | **Tester**: {Name} | **Version**: {App Version}

## Summary

| Metric | Value |
|--------|-------|
| Total Tests | X |
| Passed | X |
| Failed | X |
| Coverage | X% |

## Findings

### [CRITICAL] {Issue Title}
- **Location**: src/api/users.ts:45
- **Steps**: 1. Send POST without auth 2. Request succeeds
- **Expected**: 401 Unauthorized
- **Actual**: 201 Created
- **Fix**: Add auth middleware

## Severity Definitions

| Severity | Criteria |
|----------|----------|
| **CRITICAL** | Security vulnerability, data loss |
| **HIGH** | Major functionality broken |
| **MEDIUM** | Feature partially working |
| **LOW** | Minor issue, cosmetic |
```
