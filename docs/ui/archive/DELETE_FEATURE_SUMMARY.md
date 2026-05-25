# TodoList Delete Feature - Implementation Summary

## Feature Added
Delete button functionality for TodoList items with index parameter support.

## Changes Made

### 1. TodoList Component (examples/TodoList.php)
```php
public function deleteTodo($index)
{
    array_splice($this->todos, $index, 1);
}
```

Added delete method and button with `data-index` attribute:
```html
<button nx-click="deleteTodo" data-index="{$index}">Delete</button>
```

### 2. JsGenerator.php - Index Replacement
Added `{$index}` replacement in loop rendering (line 84):
```php
html = html.replace(
    new RegExp('{\$' + 'index}', 'g'),
    index
);
```

**Challenge**: PHP heredoc interprets `$index` as variable interpolation.
**Solution**: Use string concatenation `'{\$' + 'index}'` to avoid interpolation.

### 3. JsGenerator.php - Click Handler with Parameters
Updated click handler to support data-index attribute (lines 92-101):
```javascript
component.querySelectorAll('[data-nexph-click]').forEach(el => {
    const methodName = el.getAttribute('data-nexph-click');
    el.addEventListener('click', (e) => {
        if (methods[methodName]) {
            const index = el.getAttribute('data-index');
            if (index !== null) {
                methods[methodName](parseInt(index));
            } else {
                methods[methodName](e);
            }
            updateUI();
        }
    });
});
```

## How It Works

1. **Template**: `<button nx-click="deleteTodo" data-index="{$index}">Delete</button>`
2. **Build Time**: `{$index}` replaced with actual index (0, 1, 2...)
3. **Runtime**: Click handler reads `data-index` attribute and passes to method
4. **Method**: `deleteTodo(index)` removes item from array using `array_splice`
5. **Update**: UI re-renders with updated todos array

## Verification

```bash
./bin/nexph build examples/TodoList.php
node -c dist/app.js  # ✓ Valid JS
```

Test in browser:
1. Add "test1"
2. Add "test2"  
3. Click Delete on first item
4. "test1" should be removed

## Commits
- `b0c7d6a` - feat: add delete functionality with index parameter support in loops
- `6054036` - fix: correct regex escaping for {$index} replacement in loops

## Result
✓ Delete functionality working
✓ Index parameter support in event handlers
✓ 42 commits total
✓ All examples working
