# Forms & Validation

Declarative form validation with built-in rules.

## Basic Validation

```html
<form nx-validate-form nx-submit.prevent="save">
    <input 
        type="email" 
        nx-validate="required|email" 
        data-nexph-validate-id="email"
        placeholder="Email"
    />
    <span nx-error="email"></span>
    
    <button type="submit">Submit</button>
</form>
```

## Validation Rules

| Rule | Description | Example |
|------|-------------|---------|
| `required` | Must have value | `nx-validate="required"` |
| `email` | Valid email format | `nx-validate="email"` |
| `min:n` | Minimum length | `nx-validate="min:3"` |
| `max:n` | Maximum length | `nx-validate="max:100"` |
| `minval:n` | Minimum numeric value | `nx-validate="minval:0"` |
| `maxval:n` | Maximum numeric value | `nx-validate="maxval:100"` |
| `numeric` | Numbers only | `nx-validate="numeric"` |
| `alpha` | Letters only | `nx-validate="alpha"` |
| `alphanumeric` | Letters and numbers | `nx-validate="alphanumeric"` |
| `url` | Valid URL | `nx-validate="url"` |
| `pattern:regex` | Custom regex | `nx-validate="pattern:^[A-Z]"` |
| `confirmed` | Matches confirmation | `nx-validate="confirmed"` |

## Combining Rules

Use pipe `|` to combine:

```html
<input nx-validate="required|email" />
<input nx-validate="required|min:8|max:20" />
<input nx-validate="numeric|minval:1|maxval:100" />
```

## Error Display

```html
<input 
    nx-validate="required|email" 
    data-nexph-validate-id="email"
/>
<span nx-error="email"></span>
```

The `nx-error` element shows the first validation error for the field.

## CSS Classes

Applied automatically during validation:

| Class | When Applied |
|-------|--------------|
| `nx-valid` | Field passes all rules |
| `nx-invalid` | Field fails validation |

Style them:

```css
input.nx-valid {
    border-color: #10b981;
}

input.nx-invalid {
    border-color: #ef4444;
}

[nx-error] {
    color: #ef4444;
    font-size: 0.875rem;
}
```

## Form-Level Validation

Block submission when invalid:

```html
<form nx-validate-form nx-submit.prevent="save">
    <!-- Fields -->
    <button type="submit">Submit</button>
</form>
```

The form won't submit until all fields pass validation.

## Complete Example

```php
<?php

use Nexph\Component;

class RegistrationForm extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $passwordConfirm = '';
    public bool $submitted = false;

    public function save(): void
    {
        $this->submitted = true;
        // Handle registration
    }

    public function render(): string
    {
        return <<<HTML
<form nx-validate-form nx-submit.prevent="save" class="form">
    <div class="field">
        <label>Name</label>
        <input 
            type="text" 
            nx-model="name"
            nx-validate="required|min:2|max:50" 
            data-nexph-validate-id="name"
        />
        <span nx-error="name"></span>
    </div>
    
    <div class="field">
        <label>Email</label>
        <input 
            type="email" 
            nx-model="email"
            nx-validate="required|email" 
            data-nexph-validate-id="email"
        />
        <span nx-error="email"></span>
    </div>
    
    <div class="field">
        <label>Password</label>
        <input 
            type="password" 
            nx-model="password"
            nx-validate="required|min:8" 
            data-nexph-validate-id="password"
        />
        <span nx-error="password"></span>
    </div>
    
    <div class="field">
        <label>Confirm Password</label>
        <input 
            type="password" 
            nx-model="passwordConfirm"
            nx-validate="required|confirmed" 
            data-nexph-validate-id="passwordConfirm"
            data-nexph-confirm="password"
        />
        <span nx-error="passwordConfirm"></span>
    </div>
    
    <button type="submit">Register</button>
    
    <p nx-if="submitted">Registration successful!</p>
</form>
HTML;
    }
}
```

## Custom Patterns

Use `pattern:` for custom regex:

```html
<!-- Phone number -->
<input nx-validate="pattern:^\\+?[0-9]{10,14}$" />

<!-- Postal code -->
<input nx-validate="pattern:^[0-9]{5}$" />

<!-- Username (letters, numbers, underscore) -->
<input nx-validate="pattern:^[a-zA-Z0-9_]+$" />
```

## Programmatic API

```javascript
// Validate single field
const isValid = window.NEXPH.validate.field(inputElement);

// Validate entire form
const formValid = window.NEXPH.validate.form(formElement);

// Get errors
const errors = window.NEXPH.validate.errors('email');
```

## Validation Timing

Validation runs on:
- `input` event (as user types)
- `blur` event (when field loses focus)
- Form submission

## Next Steps

- [Build & Deploy](./build.md)
- [State Management](./state.md)
