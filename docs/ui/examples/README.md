# Examples

Code examples demonstrating Nexph features.

## Basic

| Example | Features |
|---------|----------|
| [Counter](./counter.md) | Reactive state, events |
| [Todo App](./todo.md) | Arrays, forms, loops, conditionals |

## Components

| Example | Features |
|---------|----------|
| [User Card](./user-card.md) | Computed properties, props |
| [Modal](./modal.md) | Portals, animations |
| [Tabs](./tabs.md) | Dynamic content, slots |

## Patterns

| Example | Features |
|---------|----------|
| [Shopping Cart](./shopping-cart.md) | Store, cross-component state |
| [Form Validation](./form-validation.md) | Validation rules, error display |
| [Data Fetching](./data-fetching.md) | Async data, loading states |

## Full Apps

| Example | Features |
|---------|----------|
| [Blog](./blog.md) | Routing, multi-page |
| [Dashboard](./dashboard.md) | Charts, lazy loading |

## Running Examples

```bash
# Clone repo
git clone https://github.com/nexph/nexph.git
cd nexph

# Install dependencies
composer install

# Run any example
nexph dev examples/Counter.php
nexph dev examples/TodoApp.php
```

## Scaffold New Project

```bash
nexph init my-app
cd my-app
nexph dev examples/App.php
```

Creates project with Counter and TodoApp examples included.
