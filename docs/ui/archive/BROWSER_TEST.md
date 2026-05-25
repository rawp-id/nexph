# Browser Testing Instructions

## Issue Reported
Browser console shows: `app.js:11 Uncaught SyntaxError: Unexpected token ';'`

## Verification Done
- ✓ Node.js syntax check passes for all examples
- ✓ All 4 examples build successfully
- ✓ Generated JavaScript is valid

## Likely Cause
**Browser cache** - old version of app.js with syntax errors

## Solution
1. **Hard refresh** the page:
   - Chrome/Firefox: `Ctrl+Shift+R` (Windows/Linux) or `Cmd+Shift+R` (Mac)
   - Or open DevTools → Network tab → check "Disable cache"

2. **Clear browser cache**:
   - Chrome: Settings → Privacy → Clear browsing data
   - Firefox: Settings → Privacy → Clear Data

3. **Test with new build**:
   ```bash
   ./bin/nexph build examples/TodoApp.php
   ./bin/nexph dev examples/TodoApp.php
   # Open http://localhost:8080 in browser
   # Hard refresh (Ctrl+Shift+R)
   ```

## Current Status
- All examples generate valid JavaScript
- Runtime: 5.2-5.4kb
- 28 commits, 569 LOC
- Week 2 complete

## If Error Persists
Check browser console for actual error line and content:
```javascript
// In browser console:
fetch('/app.js').then(r => r.text()).then(code => {
    console.log('Line 11:', code.split('\n')[10]);
});
```
