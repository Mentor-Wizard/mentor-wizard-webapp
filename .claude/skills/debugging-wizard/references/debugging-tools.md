# Debugging Tools

> Reference for: Debugging Wizard
> Load when: Setting up debugging environment

## Debuggers by Language

| Language | Debugger | Start Command |
|----------|----------|---------------|
| TypeScript/JS | Node Inspector | `node --inspect` |
| Python | pdb/ipdb | `python -m pdb` |
| Go | Delve | `dlv debug` |
| PHP | Xdebug | `XDEBUG_MODE=debug` |
| Rust | rust-gdb/lldb | `rust-gdb ./target/debug/app` |

## Node.js / TypeScript

```bash
# Start with inspector
node --inspect dist/main.js

# Break on first line
node --inspect-brk dist/main.js

# With ts-node
node --inspect -r ts-node/register src/main.ts
```

```typescript
// In code
debugger; // Breakpoint

// Quick print
console.log({ variable }); // Shows name and value
console.table(arrayOfObjects); // Table format
console.trace('Called from'); // Stack trace
```

## Python

```bash
# Start debugger
python -m pdb script.py

# Post-mortem on exception
python -m pdb -c continue script.py
```

```python
# In code
breakpoint()  # Python 3.7+
import pdb; pdb.set_trace()  # Older Python

# Quick print
print(f"{variable=}")  # Python 3.8+ shows name and value
```

### pdb Commands

| Command | Action |
|---------|--------|
| `n` | Next line |
| `s` | Step into |
| `c` | Continue |
| `l` | List code |
| `p expr` | Print expression |
| `w` | Where (stack) |
| `q` | Quit |

## PHP / Laravel

```php
// In code
dd($variable);       // Dump and die
dump($variable);     // Dump without dying
ray($variable);      // Ray debugger

// Xdebug
// Set in php.ini or .env:
// XDEBUG_MODE=debug
// XDEBUG_START_WITH_REQUEST=yes
```

## VS Code Debug Config

```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "type": "node",
      "request": "launch",
      "name": "Debug TypeScript",
      "program": "${workspaceFolder}/src/main.ts",
      "preLaunchTask": "tsc: build",
      "outFiles": ["${workspaceFolder}/dist/**/*.js"]
    },
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003
    }
  ]
}
```

## Quick Reference

| Need | Tool |
|------|------|
| Breakpoint in code | `debugger;` / `breakpoint()` |
| Print with name | `console.log({x})` / `print(f"{x=}")` |
| Stack trace | `console.trace()` / `traceback.print_stack()` |
| Inspect object | `console.dir(obj)` / `dir(obj)` |
