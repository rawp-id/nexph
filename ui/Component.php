<?php

namespace Nexph;

use Nexph\Runtime\EventBus;

abstract class Component
{
    protected array $props = [];
    protected array $slots = [];
    protected array $computed = [];
    protected string $componentId;
    protected array $propTypes = [];

    public function __construct(array $props = [], array $slots = [])
    {
        $this->props = $props;
        $this->slots = $slots;
        $this->componentId = uniqid('component_');
        $this->initializeComputed();
        $this->onMount();
    }

    abstract public function render(): string;

    protected function prop(string $key, mixed $default = null): mixed
    {
        return $this->props[$key] ?? $default;
    }

    protected function slot(string $name = 'default'): string
    {
        return $this->slots[$name] ?? '';
    }

    protected function emit(string $event, mixed $data = null): void
    {
        EventBus::emit($this->componentId, $event, $data);
    }

    protected function on(string $event, callable $callback): void
    {
        EventBus::listen($this->componentId, $event, $callback);
    }

    protected function computed(): array
    {
        return [];
    }

    protected function onMount(): void {}
    protected function onUpdate(): void {}
    protected function onDestroy(): void {}

    public function destroy(): void
    {
        $this->onDestroy();
        EventBus::off($this->componentId, '*');
    }

    private function initializeComputed(): void
    {
        $computedProperties = $this->computed();

        foreach ($computedProperties as $name => $callback) {
            $this->computed[$name] = $callback;
        }
    }

    public function __get(string $name)
    {
        if (isset($this->computed[$name])) {
            return call_user_func($this->computed[$name]);
        }

        return $this->$name ?? null;
    }

    public function getComponentId(): string
    {
        return $this->componentId;
    }
}
