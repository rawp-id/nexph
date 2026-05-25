# API Reference

Complete API documentation for Nexph.

## Contents

- [CLI Commands](./cli.md)
- [Component API](./component.md)
- [Runtime API](./runtime.md)
- [TypeScript Definitions](./typescript.md)

## Quick Reference

### CLI

```bash
nexph build <entry> [options]   # Compile to static files
nexph dev <entry> [options]     # Dev server with HMR
nexph serve <entry> [options]   # Build and serve
nexph init [name] [options]     # Scaffold project
nexph pages [options]           # Multi-page build
nexph test [dir] [options]      # Run tests
nexph export <entry> [options]  # Single-file export
```

### Component

```php
class MyComponent extends Component
{
    // Reactive state
    public string $prop = 'value';
    
    // Computed properties
    protected function computed(): array { }
    
    // Event emission
    public function emit(string $event, array $data = []): void { }
    
    // Required render method
    public function render(): string { }
}
```

### Runtime (JavaScript)

```javascript
// Store
window.NEXPH.store.use('name')
window.NEXPH.store.subscribe('name', callback)

// Router
window.NEXPH.router.push('/path')
window.NEXPH.router.back()
window.NEXPH.router.current
window.NEXPH.router.params

// Animations
window.NEXPH.animate.run(el, name, options)
window.NEXPH.animate.add(name, keyframes)

// Portals
window.NEXPH.portal.mount(el)
window.NEXPH.portal.unmount(el)

// Validation
window.NEXPH.validate.field(el)
window.NEXPH.validate.form(form)
```

### Directives

| Category | Directives |
|----------|------------|
| Events | `nx-click`, `nx-input`, `nx-submit`, `nx-keydown`, `nx-keyup` |
| Binding | `nx-model` |
| Conditionals | `nx-if`, `nx-show` |
| Loops | `nx-for` |
| Attributes | `nx-class`, `nx-style` |
| Routing | `nx-route`, `nx-link`, `nx-outlet` |
| Store | `nx-store-bind`, `nx-store-model`, `nx-store-action` |
| Validation | `nx-validate`, `nx-validate-form`, `nx-error` |
| Animation | `nx-animate`, `nx-animate-scroll`, `nx-animate-leave`, `nx-animate-repeat` |
| Other | `nx-portal`, `nx-lazy`, `nx-fetch` |
