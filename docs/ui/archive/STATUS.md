# NEXPH UI - Current Status

## Week 2 Complete ✓

### Completed Features
- [x] Component system with reactive state
- [x] Event binding (click, input, submit, keydown, keyup)
- [x] Event modifiers (.prevent, .enter)
- [x] Two-way binding (nx-model)
- [x] Conditional rendering (nx-if, nx-show)
- [x] Loop rendering (nx-for)
- [x] **Object property access in loops** ({$item.property})
- [x] Build pipeline and CLI tool
- [x] Dev server with auto-rebuild

### Working Examples
1. **Counter** - Simple increment/decrement (5.2kb)
2. **Card** - Toggle visibility with nx-show (5.2kb)
3. **TodoApp** - Simple todo list with strings (5.2kb)
4. **TodoList** - Complex todo with objects (5.7kb) - **NOW WORKING**

### Metrics
- **LOC**: 569 lines PHP
- **Runtime**: 5.2-5.7kb
- **Commits**: 31 total
- **Tests**: 3 PHPUnit tests passing

### Recent Fixes (2026-05-20)
1. Fixed JS syntax error in TodoApp - proper method body indentation
2. Fixed loop template variable replacement - escaped `$` in regex
3. Fixed PHP to JS conversion for array literals (`[]` → `{}`)
4. Fixed `count()` function conversion to `.length`
5. **Added object property access in loops** - `{$todo.text}` now works
6. All 4 examples now build with valid JavaScript

### TodoList Example
```php
<li nx-for="todo in todos">
    <input type="checkbox" />
    <span>{$todo.text}</span>  <!-- Property access works! -->
    <button>Delete</button>
</li>
```

Generated JS supports:
- `{$item.property}` - Access object properties
- `{$item}` - Fallback to JSON.stringify for whole object

### Known Issues
- Browser cache may show old errors - hard refresh (Ctrl+Shift+R)

### Next Steps
- Week 3: Component props, slots, nested components
- Advanced features: nx-class, nx-style, computed properties
- Event handlers in loops (delete button)
- Checkbox binding for todo.done

## Build & Test
```bash
# Build TodoList
./bin/nexph build examples/TodoList.php

# Start dev server
./bin/nexph dev examples/TodoList.php

# Run tests
vendor/bin/phpunit
```

## Architecture
- **Compile-time framework**: PHP → HTML/CSS/JS at build time
- **No virtual DOM**: Direct DOM manipulation for efficiency
- **Regex-based conversion**: PHP patterns → JS equivalents
- **Template cloning**: nx-for uses DOM cloning for loops
- **Property access**: Supports `{$item.property}` in loop templates
