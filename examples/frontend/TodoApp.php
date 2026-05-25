<?php

use Nexph\Component;

class TodoApp extends Component
{
    public array $todos = [];
    public string $newTodo = '';

    public function addTodo()
    {
        if (!empty($this->newTodo)) {
            $this->todos[] = $this->newTodo;
            $this->newTodo = '';
        }
    }

    public function render(): string
    {
        return <<<HTML
<div class="todo-app">
    <h1>Todo List</h1>
    
    <form nx-submit.prevent="addTodo">
        <input 
            type="text" 
            nx-model="newTodo"
            placeholder="Add new todo..."
        />
        <button type="submit">Add</button>
    </form>
    
    <ul>
        <li nx-for="todo in todos">{$todo}</li>
    </ul>
    
    <p nx-if="todos">You have tasks!</p>
    <p nx-show="newTodo">Typing: {$this->newTodo}</p>
</div>
HTML;
    }
}
