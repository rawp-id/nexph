# User Card Example

Component with computed properties and props.

## Code

```php
<?php
// src/Components/UserCard.php

use Nexph\Component;

class UserCard extends Component
{
    public string $firstName = 'John';
    public string $lastName = 'Doe';
    public string $email = 'john@example.com';
    public string $avatar = '';
    public bool $online = false;

    protected function computed(): array
    {
        return [
            'fullName' => fn() => "{$this->firstName} {$this->lastName}",
            'initials' => fn() => strtoupper(
                substr($this->firstName, 0, 1) . 
                substr($this->lastName, 0, 1)
            ),
            'statusText' => fn() => $this->online ? 'Online' : 'Offline',
        ];
    }

    public function render(): string
    {
        return <<<HTML
<div class="user-card">
    <div class="avatar" nx-class="{ 'online': online }">
        <span nx-if="!avatar">{$this->initials}</span>
        <img nx-if="avatar" src="{$this->avatar}" alt="{$this->fullName}" />
    </div>
    
    <div class="info">
        <h3>{$this->fullName}</h3>
        <p class="email">{$this->email}</p>
        <span class="status" nx-class="{ 'online': online }">
            {$this->statusText}
        </span>
    </div>
</div>

<style>
.user-card {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    max-width: 300px;
}
.avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #6b7280;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    position: relative;
}
.avatar.online::after {
    content: '';
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 12px;
    height: 12px;
    background: #10b981;
    border: 2px solid white;
    border-radius: 50%;
}
.avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}
.info h3 {
    margin: 0 0 0.25rem;
}
.email {
    margin: 0;
    color: #6b7280;
    font-size: 0.875rem;
}
.status {
    font-size: 0.75rem;
    color: #6b7280;
}
.status.online {
    color: #10b981;
}
</style>
HTML;
    }
}
```

## Usage

```html
<UserCard 
    firstName="Jane" 
    lastName="Smith" 
    email="jane@example.com"
    online="true"
/>

<UserCard 
    firstName="Bob" 
    lastName="Wilson"
    avatar="https://example.com/bob.jpg"
/>
```

## Features Demonstrated

- Props with defaults
- Computed properties
- Conditional rendering
- Dynamic classes
- Scoped styles

## Run

```bash
nexph dev src/Components/UserCard.php
```
