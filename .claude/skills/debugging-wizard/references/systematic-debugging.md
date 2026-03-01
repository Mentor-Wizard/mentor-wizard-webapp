# Systematic Debugging

> Reference for: Debugging Wizard
> Load when: complex bugs, multiple failed fixes, root cause analysis

---

## Core Principle

> **NO FIXES WITHOUT ROOT CAUSE INVESTIGATION FIRST.**

Jumping to fixes without understanding causes creates more bugs. Systematic debugging prevents the "fix one thing, break two more" cycle.

---

## The Four Mandatory Phases

```
Phase 1: ROOT CAUSE INVESTIGATION
├── Read error messages thoroughly
├── Reproduce reliably with documented steps
├── Examine recent changes
└── Trace data flow backward

Phase 2: PATTERN ANALYSIS
├── Find similar working implementations
├── Study reference implementations completely
└── Document all differences

Phase 3: HYPOTHESIS TESTING
├── Form specific, written hypothesis
├── Test with minimal, isolated changes
└── One variable at a time

Phase 4: IMPLEMENTATION
├── Create failing test case
├── Implement single fix addressing root cause
└── Verify no new breakage
```

---

## Phase 1: Root Cause Investigation

### Step 1.1: Read Error Messages Thoroughly

```bash
# Don't just read the first line
TypeError: Cannot read property 'map' of undefined
    at UserList.render (UserList.tsx:24)
    at renderWithHooks (react-dom.js:14985)
```

**Key questions:**
- What exact operation failed?
- Where in the code (file, line)?
- What was the call stack?

### Step 1.2: Reproduce Reliably

Document exact steps that reproduce the bug 100% of the time.

### Step 1.3: Examine Recent Changes

```bash
git log --oneline -10
git log -p UserList.tsx
git bisect start
```

### Step 1.4: Trace Data Flow Backward

```typescript
// Error happens here:
users.map(u => u.name)  // users is undefined

// Trace backward: Where does 'users' come from?
const users = props.users;
// Where do props come from?
<UserList users={data.users} />
// Where does data come from?
const { data } = useQuery(GET_USERS);

// ROOT CAUSE: Query returns { users: null } when loading
```

---

## Phase 2: Pattern Analysis

Find working examples to understand what correct behavior looks like.

```bash
# Find similar components that work correctly
grep -r "useQuery" src/components/ --include="*.tsx"
```

---

## Phase 3: Hypothesis Testing

### Form Specific, Written Hypothesis

```markdown
## Hypothesis #1
**Statement:** The crash occurs because `users` is undefined when the
query is complete but returns no data.

**Prediction:** Adding a null check before `.map()` will prevent the crash.

**Test:** Add `if (!users) return null;` before the map call.
```

### One Variable at a Time

Do NOT test multiple hypotheses simultaneously.

---

## Phase 4: Implementation

### Create Failing Test Case First

```typescript
it('should handle undefined users gracefully', () => {
  const { container } = render(<UserList users={undefined} />);
  expect(container).not.toThrow();
});
```

### Verify No New Breakage

```bash
npm test
npm run test:integration
```

---

## The Three-Fix Threshold

> **After 3 failed fix attempts → STOP.**

Three failures in different locations signals architectural problems, not isolated bugs.

At the threshold:
1. Stop fixing symptoms
2. Document the pattern of failures
3. Identify architectural assumptions being violated
4. Propose structural change rather than patch
5. Discuss with team before proceeding

---

## Red Flags Requiring Process Reset

| Red Flag | Why It's Wrong |
|----------|----------------|
| Proposing solutions before tracing data flow | Guessing, not debugging |
| Making multiple simultaneous changes | Can't identify which change worked |
| Skipping test creation | Bug will recur |
| "Let's try this and see if it works" | Shotgun debugging |
| Fixing without understanding the cause | Band-aid, not cure |

---

*Adapted from [obra/superpowers](https://github.com/obra/superpowers) by Jesse Vincent (@obra), MIT License.*
