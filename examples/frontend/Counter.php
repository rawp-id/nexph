<?php

use Nexph\Component;

class Counter extends Component
{
    public int $count = 0;

    public function increment()
    {
        $this->count++;
    }

    public function render(): string
    {
        return <<<HTML
<div class="card">
    <h1>{$this->count}</h1>
    <button nx-click="increment">Tambah</button>
</div>
HTML;
    }
}
