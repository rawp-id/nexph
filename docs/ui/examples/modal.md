# Modal Example

Modal dialog using portals and animations.

## Code

```php
<?php
// src/Components/Modal.php

use Nexph\Component;

class Modal extends Component
{
    public bool $open = false;
    public string $title = 'Modal';
    public string $size = 'md'; // sm, md, lg

    public function show(): void
    {
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
        $this->emit('close');
    }

    public function confirm(): void
    {
        $this->emit('confirm');
        $this->close();
    }

    public function render(): string
    {
        return <<<HTML
<div nx-portal="#modals" nx-if="open">
    <div 
        class="modal-backdrop" 
        nx-click="close"
        nx-animate="fade-in:duration=200"
        nx-animate-leave="fade-out:duration=150"
    ></div>
    
    <div 
        class="modal"
        nx-class="{ 'modal-sm': size === 'sm', 'modal-lg': size === 'lg' }"
        nx-animate="zoom-in:duration=200"
        nx-animate-leave="zoom-out:duration=150"
    >
        <div class="modal-header">
            <h2>{$this->title}</h2>
            <button class="close-btn" nx-click="close">×</button>
        </div>
        
        <div class="modal-body">
            <slot />
        </div>
        
        <div class="modal-footer">
            <button class="btn btn-secondary" nx-click="close">Cancel</button>
            <button class="btn btn-primary" nx-click="confirm">Confirm</button>
        </div>
    </div>
</div>

<style>
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100;
}
.modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 8px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    z-index: 101;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow: auto;
}
.modal-sm { max-width: 300px; }
.modal-lg { max-width: 800px; }
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
}
.modal-header h2 {
    margin: 0;
    font-size: 1.25rem;
}
.close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
}
.modal-body {
    padding: 1rem;
}
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
}
.btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
}
.btn-secondary {
    background: #f3f4f6;
    border: 1px solid #d1d5db;
}
.btn-primary {
    background: #3b82f6;
    border: none;
    color: white;
}
</style>
HTML;
    }
}
```

## Usage

```php
<?php

use Nexph\Component;

class App extends Component
{
    public bool $showModal = false;

    public function openModal(): void
    {
        $this->showModal = true;
    }

    public function onConfirm(): void
    {
        // Handle confirmation
    }

    public function render(): string
    {
        return <<<HTML
<div class="app">
    <button nx-click="openModal">Open Modal</button>
    
    <Modal 
        title="Confirm Action" 
        open="{$this->showModal}"
        @close="showModal = false"
        @confirm="onConfirm"
    >
        <p>Are you sure you want to proceed?</p>
    </Modal>
</div>

<!-- Portal container -->
<div id="modals"></div>
HTML;
    }
}
```

## Features Demonstrated

- Portals (`nx-portal`)
- Enter/exit animations
- Event emission
- Slots for content projection
- Dynamic classes
- Backdrop click to close

## Run

```bash
nexph dev src/Components/Modal.php
```
