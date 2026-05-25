# TypeScript Definitions

Nexph includes TypeScript definitions in `nexph.d.ts`.

## Installation

The type definitions are included with the package:

```bash
composer require nexph/nexph
```

Reference in your `tsconfig.json`:

```json
{
  "compilerOptions": {
    "types": ["./vendor/nexph/nexph/nexph.d.ts"]
  }
}
```

Or copy to your project:

```bash
cp vendor/nexph/nexph/nexph.d.ts src/
```

## Global Types

### Window.NEXPH

```typescript
interface Window {
    NEXPH: {
        store: NexphStoreAPI;
        router: NexphRouterAPI;
        animate: NexphAnimateAPI;
        portal: NexphPortalAPI;
        validate: NexphValidateAPI;
        lazy: NexphLazyAPI;
        events: NexphEventBus;
        components: NexphComponentRegistry;
        devtools?: NexphDevTools;
    };
}
```

## Store Types

```typescript
interface NexphStoreAPI {
    use<T = any>(name: string): T;
    subscribe(name: string, callback: (state: any) => void): () => void;
}

// Usage
const cart = window.NEXPH.store.use<CartState>('cart');
```

## Router Types

```typescript
interface NexphRouterAPI {
    push(path: string): void;
    back(): void;
    current: string;
    params: Record<string, string>;
}
```

## Animation Types

```typescript
interface NexphAnimateAPI {
    run(el: Element, name: NexphAnimationName, options?: AnimateOptions): Promise<void>;
    add(name: string, keyframes: Keyframe[]): void;
}

interface AnimateOptions {
    duration?: number;
    easing?: string;
}

type NexphAnimationName =
    | 'fade-in'
    | 'fade-out'
    | 'slide-up'
    | 'slide-down'
    | 'slide-left'
    | 'slide-right'
    | 'zoom-in'
    | 'zoom-out'
    | 'bounce'
    | 'shake'
    | 'pulse'
    | 'flip'
    | string;
```

## Validation Types

```typescript
interface NexphValidateAPI {
    field(el: HTMLInputElement): boolean;
    form(form: HTMLFormElement): boolean;
}
```

## Component Types

```typescript
interface NexphComponent {
    id: string;
    state: Record<string, any>;
    methods: Record<string, Function>;
    refs: Record<string, Element>;
}

interface NexphComponentRegistry {
    get(id: string): NexphComponent | undefined;
    all(): NexphComponent[];
}
```

## Event Bus Types

```typescript
interface NexphEventBus {
    on(event: string, callback: (data: any) => void): void;
    off(event: string, callback: (data: any) => void): void;
    emit(event: string, data?: any): void;
}
```

## Directive Type Aliases

For JSX/TSX support:

```typescript
type NxClick = string;
type NxInput = string;
type NxSubmit = string;
type NxModel = string;
type NxIf = string;
type NxShow = string;
type NxFor = string;
type NxClass = string;
type NxStyle = string;
type NxRoute = string;
type NxLink = string;
type NxAnimate = string;
type NxPortal = string;
type NxValidate = string;
type NxLazy = string;
```

## JSX Augmentation

For React/Preact projects using Nexph directives:

```typescript
declare namespace JSX {
    interface IntrinsicElements {
        [elemName: string]: any;
    }
    
    interface HTMLAttributes<T> {
        'nx-click'?: string;
        'nx-input'?: string;
        'nx-submit'?: string;
        'nx-model'?: string;
        'nx-if'?: string;
        'nx-show'?: string;
        'nx-for'?: string;
        'nx-class'?: string;
        'nx-style'?: string;
        'nx-route'?: string;
        'nx-link'?: string;
        'nx-animate'?: string;
        'nx-animate-scroll'?: string;
        'nx-animate-leave'?: string;
        'nx-portal'?: string;
        'nx-validate'?: string;
        'nx-lazy'?: string;
    }
}
```

## Example Usage

```typescript
// Type-safe store access
interface CartState {
    items: Array<{ id: number; name: string; price: number }>;
    total: number;
    coupon: string;
}

const cart = window.NEXPH.store.use<CartState>('cart');
cart.items.push({ id: 1, name: 'Product', price: 99 });

// Type-safe animation
await window.NEXPH.animate.run(element, 'fade-in', {
    duration: 500,
    easing: 'ease-out'
});

// Type-safe router
window.NEXPH.router.push('/users/' + userId);
const { id } = window.NEXPH.router.params;

// Type-safe validation
const form = document.querySelector('form') as HTMLFormElement;
if (window.NEXPH.validate.form(form)) {
    // Submit
}
```

## Full Definition File

See `nexph.d.ts` in the package root for complete type definitions.
