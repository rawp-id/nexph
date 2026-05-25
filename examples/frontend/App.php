<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Nexph\Component;
use Nexph\Runtime\Store;

// shared store — cart
Store::define('cart', [
    'items'  => [],
    'count'  => 0,
    'coupon' => '',
], [
    'add'   => fn() => null,
    'clear' => fn() => null,
]);

class App extends Component
{
    public string $currentView = 'home';
    public int $counter = 0;
    public array $todos = [];
    public string $newTodo = '';
    public array $posts = [];
    public string $email = '';
    public string $password = '';
    public bool $formSent = false;
    public bool $showTooltip = false;

    public function incrementCounter(): void { $this->counter++; }
    public function decrementCounter(): void { $this->counter--; }
    public function resetCounter(): void     { $this->counter = 0; }

    public function addTodo(): void
    {
        if (!empty($this->newTodo)) {
            $this->todos[] = ['text' => $this->newTodo, 'done' => false];
            $this->newTodo = '';
        }
    }

    public function toggleTodo(int $index): void
    {
        if (isset($this->todos[$index])) {
            $this->todos[$index]['done'] = !$this->todos[$index]['done'];
        }
    }

    public function deleteTodo(int $index): void
    {
        if (isset($this->todos[$index])) {
            array_splice($this->todos, $index, 1);
        }
    }

    public function submitForm(): void
    {
        $this->formSent = true;
    }

    public function toggleTooltip(): void
    {
        $this->showTooltip = !$this->showTooltip;
    }

    public function style(): string
    {
        return <<<CSS
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: #0f0f1a;
    min-height: 100vh;
    padding: 24px 16px;
    color: #e2e8f0;
}

.app { max-width: 900px; margin: 0 auto; }

.header {
    background: linear-gradient(135deg, #1e1e3a 0%, #16213e 100%);
    border: 1px solid rgba(99,102,241,0.25);
    padding: 20px 24px;
    border-radius: 16px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    box-shadow: 0 4px 24px rgba(0,0,0,0.4);
}

.header-brand { display: flex; align-items: center; gap: 12px; }

.header-logo {
    width: 36px; height: 36px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    box-shadow: 0 0 16px rgba(99,102,241,0.4);
}

.header h1 {
    font-size: 20px; font-weight: 700;
    background: linear-gradient(135deg, #a5b4fc, #c4b5fd);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}

.nav {
    display: flex; gap: 4px; flex-wrap: wrap;
    background: rgba(0,0,0,0.3);
    padding: 5px; border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.06);
}

.nav button {
    padding: 7px 14px; border: none; border-radius: 8px;
    background: transparent; color: #94a3b8;
    cursor: pointer; font-size: 13px; font-weight: 500;
    transition: all 0.2s; font-family: inherit;
}

.nav button:hover { background: rgba(99,102,241,0.15); color: #c7d2fe; }

.nav button.active {
    background: linear-gradient(135deg, #6366f1, #7c3aed);
    color: #fff;
    box-shadow: 0 2px 12px rgba(99,102,241,0.45);
}

.nav-btn {
    display: inline-block;
    padding: 7px 14px; border-radius: 8px;
    background: transparent; color: #94a3b8;
    cursor: pointer; font-size: 13px; font-weight: 500;
    transition: all 0.2s; text-decoration: none;
}

.nav-btn:hover { background: rgba(99,102,241,0.15); color: #c7d2fe; }

.nav-btn.router-link-active {
    background: linear-gradient(135deg, #6366f1, #7c3aed);
    color: #fff;
    box-shadow: 0 2px 12px rgba(99,102,241,0.45);
}

.content {
    background: linear-gradient(160deg, #1a1a2e 0%, #16213e 100%);
    border: 1px solid rgba(99,102,241,0.2);
    padding: 32px 28px;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.4);
    min-height: 420px;
}

/* ── Home ── */
.home-view { text-align: center; }
.home-badge {
    display: inline-block;
    background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.35);
    color: #a5b4fc; font-size: 12px; font-weight: 600;
    padding: 4px 12px; border-radius: 20px;
    letter-spacing: 0.8px; text-transform: uppercase; margin-bottom: 16px;
}
.home-view h2 {
    font-size: 32px; font-weight: 800;
    background: linear-gradient(135deg, #e2e8f0 0%, #a5b4fc 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; margin-bottom: 10px;
}
.home-view > p { font-size: 15px; color: #64748b; margin-bottom: 32px; }
.features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 14px; text-align: left;
}
.feature {
    padding: 18px; background: rgba(255,255,255,0.03);
    border-radius: 12px; border: 1px solid rgba(255,255,255,0.07);
    transition: all 0.2s;
}
.feature:hover {
    border-color: rgba(99,102,241,0.35);
    background: rgba(99,102,241,0.07);
    transform: translateY(-2px);
}
.feature-icon { font-size: 22px; margin-bottom: 8px; display: block; }
.feature h3 { font-size: 13px; font-weight: 700; color: #c7d2fe; margin-bottom: 4px; }
.feature p  { font-size: 12px; color: #475569; line-height: 1.5; }

/* ── Counter ── */
.counter-view { text-align: center; padding: 16px 0; }
.counter-view h2 { font-size: 22px; font-weight: 700; color: #c7d2fe; margin-bottom: 6px; }
.counter-subtitle { font-size: 14px; color: #475569; margin-bottom: 36px; }
.counter-display {
    font-size: 72px; font-weight: 800;
    background: linear-gradient(135deg, #a5b4fc, #c4b5fd);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; margin-bottom: 36px; line-height: 1;
}
.counter-actions { display: flex; gap: 12px; justify-content: center; }

/* ── Buttons ── */
.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    padding: 11px 22px; font-size: 14px; font-weight: 600;
    border: none; border-radius: 10px; cursor: pointer;
    transition: all 0.2s; font-family: inherit;
}
.btn-primary {
    background: linear-gradient(135deg, #6366f1, #7c3aed); color: #fff;
    box-shadow: 0 4px 16px rgba(99,102,241,0.35);
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(99,102,241,0.5); }
.btn-ghost {
    background: rgba(255,255,255,0.05); color: #94a3b8;
    border: 1px solid rgba(255,255,255,0.08);
}
.btn-ghost:hover { background: rgba(255,255,255,0.09); color: #c7d2fe; }
.btn-success {
    background: rgba(34,197,94,0.12); color: #4ade80;
    border: 1px solid rgba(34,197,94,0.25);
}
.btn-icon { padding: 10px; border-radius: 8px; font-size: 16px; }

/* ── Todos ── */
.todos-view h2 { font-size: 22px; font-weight: 700; color: #c7d2fe; margin-bottom: 6px; }
.todos-subtitle { font-size: 14px; color: #475569; margin-bottom: 20px; }
.todo-form { display: flex; gap: 10px; margin-bottom: 20px; }
.todo-form input {
    flex: 1; padding: 11px 14px;
    background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px; font-size: 14px; color: #e2e8f0; font-family: inherit;
    transition: all 0.2s; outline: none;
}
.todo-form input:focus {
    border-color: rgba(99,102,241,0.5);
    box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
}
.todo-list { list-style: none; display: flex; flex-direction: column; gap: 8px; }
.todo-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px;
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);
    border-radius: 10px; transition: all 0.2s;
}
.todo-item:hover { border-color: rgba(99,102,241,0.25); }
.todo-item.done { opacity: 0.5; }
.todo-item span { flex: 1; font-size: 14px; color: #cbd5e1; }
.todo-item.done span { text-decoration: line-through; color: #334155; }
.todo-delete {
    opacity: 0; background: none; border: none; color: #ef4444;
    cursor: pointer; font-size: 15px; padding: 3px 6px;
    border-radius: 6px; transition: all 0.2s; font-family: inherit;
}
.todo-item:hover .todo-delete { opacity: 1; }
.todo-empty { text-align: center; padding: 36px 20px; color: #334155; font-size: 14px; }
.todo-empty-icon { font-size: 36px; display: block; margin-bottom: 10px; opacity: 0.4; }
.todo-stats {
    margin-top: 14px; padding: 10px 14px;
    background: rgba(99,102,241,0.07); border: 1px solid rgba(99,102,241,0.15);
    border-radius: 10px; font-size: 13px; color: #64748b;
    display: flex; align-items: center; gap: 6px;
}
.todo-stats strong { color: #a5b4fc; font-weight: 700; }

/* ── Fetch ── */
.fetch-view h2 { font-size: 22px; font-weight: 700; color: #c7d2fe; margin-bottom: 6px; }
.fetch-subtitle { font-size: 14px; color: #475569; margin-bottom: 20px; }
.post-list { list-style: none; display: flex; flex-direction: column; gap: 10px; }
.post-item {
    padding: 14px 16px;
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);
    border-radius: 10px; transition: border-color 0.2s;
}
.post-item:hover { border-color: rgba(99,102,241,0.3); }
.post-title { font-size: 13px; font-weight: 600; color: #c7d2fe; margin-bottom: 4px; }
.post-body  { font-size: 12px; color: #475569; line-height: 1.5; }
.fetch-empty { text-align: center; padding: 36px 20px; color: #334155; font-size: 14px; }

/* ── Store ── */
.store-view h2 { font-size: 22px; font-weight: 700; color: #c7d2fe; margin-bottom: 6px; }
.store-subtitle { font-size: 14px; color: #475569; margin-bottom: 24px; }
.store-card {
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px; padding: 20px; margin-bottom: 16px;
}
.store-card h3 { font-size: 14px; font-weight: 700; color: #a5b4fc; margin-bottom: 14px; }
.store-row { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.store-label { font-size: 13px; color: #64748b; min-width: 80px; }
.store-value {
    font-size: 20px; font-weight: 800; color: #c7d2fe;
    background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2);
    border-radius: 8px; padding: 6px 16px; min-width: 60px; text-align: center;
}
.store-input {
    flex: 1; padding: 9px 12px;
    background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px; font-size: 14px; color: #e2e8f0; font-family: inherit;
    outline: none; transition: all 0.2s;
}
.store-input:focus { border-color: rgba(99,102,241,0.5); }
.store-actions { display: flex; gap: 8px; flex-wrap: wrap; }

/* ── Animate ── */
.animate-view h2 { font-size: 22px; font-weight: 700; color: #c7d2fe; margin-bottom: 6px; }
.animate-subtitle { font-size: 14px; color: #475569; margin-bottom: 24px; }
.anim-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 14px; margin-bottom: 20px;
}
.anim-card {
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    padding: 20px 14px;
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);
    border-radius: 12px; text-align: center; cursor: default;
    transition: border-color 0.2s;
}
.anim-card:hover { border-color: rgba(99,102,241,0.35); }
.anim-icon { font-size: 28px; }
.anim-card strong { font-size: 13px; color: #a5b4fc; font-weight: 700; }
.anim-card span { font-size: 11px; color: #475569; }
.anim-info {
    padding: 12px 16px;
    background: rgba(99,102,241,0.07); border: 1px solid rgba(99,102,241,0.15);
    border-radius: 10px; font-size: 12px; color: #64748b;
    display: flex; flex-wrap: wrap; gap: 8px; align-items: center;
}
.anim-info code { color: #a5b4fc; background: rgba(99,102,241,0.1); padding: 2px 6px; border-radius: 4px; }

/* ── Portal ── */
.portal-view h2 { font-size: 22px; font-weight: 700; color: #c7d2fe; margin-bottom: 6px; }
.portal-subtitle { font-size: 14px; color: #475569; margin-bottom: 24px; }
.portal-demo { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.portal-card {
    padding: 20px;
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);
    border-radius: 12px;
}
.portal-card h3 { font-size: 14px; font-weight: 700; color: #a5b4fc; margin-bottom: 10px; }
.portal-card p  { font-size: 13px; color: #64748b; line-height: 1.7; }
.portal-card code { color: #a5b4fc; }
@media (max-width: 600px) { .portal-demo { grid-template-columns: 1fr; } }

.validate-view h2 { font-size: 22px; font-weight: 700; color: #c7d2fe; margin-bottom: 6px; }
.validate-subtitle { font-size: 14px; color: #475569; margin-bottom: 24px; }
.form-card {
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px; padding: 24px; max-width: 480px;
}
.form-group { margin-bottom: 18px; }
.form-label { display: block; font-size: 13px; font-weight: 600; color: #94a3b8; margin-bottom: 6px; }
.form-input {
    width: 100%; padding: 11px 14px;
    background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px; font-size: 14px; color: #e2e8f0; font-family: inherit;
    outline: none; transition: all 0.2s;
}
.form-input:focus { border-color: rgba(99,102,241,0.5); box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }
.form-input.nx-invalid { border-color: rgba(239,68,68,0.6); box-shadow: 0 0 0 3px rgba(239,68,68,0.1); }
.form-input.nx-valid   { border-color: rgba(34,197,94,0.5); }
.form-error { font-size: 12px; color: #f87171; margin-top: 5px; min-height: 16px; }
.form-success {
    padding: 12px 16px; background: rgba(34,197,94,0.1);
    border: 1px solid rgba(34,197,94,0.25); border-radius: 10px;
    color: #4ade80; font-size: 14px; margin-top: 16px;
}
CSS;
    }

    public function render(): string
    {
        return <<<HTML
<div class="app">
    <div class="header">
        <div class="header-brand">
            <div class="header-logo">⚡</div>
            <h1>NEXPH UI</h1>
        </div>
        <div class="nav">
            <a nx-link="/"         class="nav-btn">Home</a>
            <a nx-link="/counter"  class="nav-btn">Counter</a>
            <a nx-link="/todos"    class="nav-btn">Todos</a>
            <a nx-link="/fetch"    class="nav-btn">Fetch</a>
            <a nx-link="/store"    class="nav-btn">Store</a>
            <a nx-link="/validate" class="nav-btn">Validate</a>
            <a nx-link="/animate"  class="nav-btn">Animate</a>
            <a nx-link="/portal"   class="nav-btn">Portal</a>
        </div>
    </div>

    <div class="content" nx-outlet></div>

    <!-- route views -->
    <div nx-route="/" data-nexph-component="home" style="display:none">
        <div class="home-view">
            <div class="home-badge">v0.8.0 — Week 8</div>
            <h2>Modern PHP Frontend</h2>
            <p>Compile-time optimized · Runtime lightweight · Zero dependencies</p>
            <div class="features">
                <div class="feature"><span class="feature-icon">⚡</span><h3>Blazing Fast</h3><p>~2ms build, 12 KB runtime</p></div>
                <div class="feature"><span class="feature-icon">🎨</span><h3>Scoped CSS</h3><p>Component-level isolation</p></div>
                <div class="feature"><span class="feature-icon">🔥</span><h3>Hot Reload</h3><p>WebSocket HMR</p></div>
                <div class="feature"><span class="feature-icon">📦</span><h3>Static Export</h3><p>Single-file, CDN-ready</p></div>
                <div class="feature"><span class="feature-icon">🌐</span><h3>nx-fetch</h3><p>Declarative async data</p></div>
                <div class="feature"><span class="feature-icon">🗺️</span><h3>Router</h3><p>Hash &amp; history modes</p></div>
                <div class="feature"><span class="feature-icon">📱</span><h3>PWA</h3><p>Manifest + service worker</p></div>
                <div class="feature"><span class="feature-icon">🗜️</span><h3>Gzip</h3><p>.gz sidecar assets</p></div>
                <div class="feature"><span class="feature-icon">🏪</span><h3>nx-store</h3><p>Shared reactive state</p></div>
                <div class="feature"><span class="feature-icon">✅</span><h3>nx-validate</h3><p>Declarative validation</p></div>
                <div class="feature"><span class="feature-icon">💤</span><h3>nx-lazy</h3><p>Lazy component loading</p></div>
                <div class="feature"><span class="feature-icon">📊</span><h3>Analyzer</h3><p>Bundle size report</p></div>
                <div class="feature"><span class="feature-icon">🎬</span><h3>nx-animate</h3><p>12 built-in animations</p></div>
                <div class="feature"><span class="feature-icon">🪟</span><h3>nx-portal</h3><p>Teleport to any DOM target</p></div>
                <div class="feature"><span class="feature-icon">🧪</span><h3>nexph test</h3><p>Component test runner</p></div>
                <div class="feature"><span class="feature-icon">📄</span><h3>Multi-page</h3><p>nexph pages command</p></div>
            </div>
        </div>
    </div>

    <div nx-route="/counter" data-nexph-component="counter" style="display:none">
        <div class="counter-view">
            <h2>Counter</h2>
            <p class="counter-subtitle">Reactive state — click to update</p>
            <div class="counter-display">{$this->counter}</div>
            <div class="counter-actions">
                <button class="btn btn-ghost btn-icon" nx-click="decrementCounter">−</button>
                <button class="btn btn-primary" nx-click="incrementCounter">+ Increment</button>
                <button class="btn btn-ghost btn-icon" nx-click="resetCounter">↺</button>
            </div>
        </div>
    </div>

    <div nx-route="/todos" data-nexph-component="todos" style="display:none">
        <div class="todos-view">
            <h2>Todo List</h2>
            <p class="todos-subtitle">Stay on top of your tasks</p>
            <form class="todo-form" nx-submit.prevent="addTodo">
                <input type="text" nx-model="newTodo" placeholder="What needs to be done?" />
                <button type="submit" class="btn btn-primary">Add</button>
            </form>
            <ul class="todo-list">
                <li class="todo-item" nx-for="todo in todos" nx-class="{'done': todo.done}">
                    <input type="checkbox" class="btn-ghost btn-icon" nx-click="toggleTodo" data-index="{\$index}" />
                    <span>{\$todo.text}</span>
                    <button class="todo-delete" nx-click="deleteTodo" data-index="{\$index}">✕</button>
                </li>
            </ul>
            <div class="todo-empty" nx-if="!todos">
                <span class="todo-empty-icon">📋</span>No tasks yet — add one above
            </div>
            <div class="todo-stats" nx-if="todos">
                <span>📊</span>
                <span>You have <strong><span data-nexph-bind="todos"></span></strong> task(s)</span>
            </div>
        </div>
    </div>

    <div nx-route="/fetch" data-nexph-component="fetch" style="display:none">
        <div class="fetch-view">
            <h2>nx-fetch Demo</h2>
            <p class="fetch-subtitle">Declarative async data — no boilerplate</p>
            <div
                nx-fetch="https://jsonplaceholder.typicode.com/posts?_limit=5"
                data-nexph-fetch-target="posts"
            ></div>
            <ul class="post-list" nx-if="posts">
                <li class="post-item" nx-for="post in posts">
                    <div class="post-title">{\$post.title}</div>
                    <div class="post-body">{\$post.body}</div>
                </li>
            </ul>
            <div class="fetch-empty" nx-if="!posts">
                <span>⏳ Loading posts...</span>
            </div>
        </div>
    </div>

    <div nx-route="/store" data-nexph-component="store" style="display:none">
        <div class="store-view">
            <h2>nx-store Demo</h2>
            <p class="store-subtitle">Shared reactive state across components</p>
            <div class="store-card">
                <h3>🏪 Cart Store</h3>
                <div class="store-row">
                    <span class="store-label">Items</span>
                    <div class="store-value" nx-store-bind="cart.count">0</div>
                </div>
                <div class="store-row">
                    <span class="store-label">Coupon</span>
                    <input class="store-input" nx-store-model="cart.coupon" placeholder="Enter coupon code..." />
                </div>
                <div class="store-actions">
                    <button class="btn btn-primary" nx-store-action="cart.add">+ Add Item</button>
                    <button class="btn btn-ghost" nx-store-action="cart.clear">Clear Cart</button>
                </div>
            </div>
            <div class="store-card">
                <h3>💡 How it works</h3>
                <p style="font-size:13px;color:#64748b;line-height:1.7">
                    Define stores in PHP with <code style="color:#a5b4fc">Store::define()</code>.
                    Bind to DOM with <code style="color:#a5b4fc">nx-store-bind</code>,
                    <code style="color:#a5b4fc">nx-store-model</code>, and
                    <code style="color:#a5b4fc">nx-store-action</code>.
                    State is reactive via JS Proxy — updates propagate instantly.
                </p>
            </div>
        </div>
    </div>

    <div nx-route="/validate" data-nexph-component="validate" style="display:none">
        <div class="validate-view">
            <h2>nx-validate Demo</h2>
            <p class="validate-subtitle">Declarative form validation — 12 built-in rules</p>
            <div class="form-card">
                <form nx-validate-form nx-submit.prevent="submitForm">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input
                            class="form-input" type="email" name="email"
                            nx-model="email"
                            nx-validate="required|email"
                            data-nexph-validate-id="email"
                            placeholder="you@example.com"
                        />
                        <div class="form-error" nx-error="email"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input
                            class="form-input" type="password" name="password"
                            nx-model="password"
                            nx-validate="required|min:8"
                            data-nexph-validate-id="password"
                            placeholder="Min 8 characters"
                        />
                        <div class="form-error" nx-error="password"></div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%">Submit</button>
                </form>
                <div class="form-success" nx-if="formSent">
                    ✓ Form submitted successfully!
                </div>
            </div>
        </div>
    </div>

    <div nx-route="/animate" data-nexph-component="animate" style="display:none">
        <div class="animate-view">
            <h2>nx-animate Demo</h2>
            <p class="animate-subtitle">12 built-in animations — enter, scroll, leave, repeat</p>
            <div class="anim-grid">
                <div class="anim-card" nx-animate="fade-in">
                    <span class="anim-icon">✨</span>
                    <strong>fade-in</strong>
                    <span>Enter on mount</span>
                </div>
                <div class="anim-card" nx-animate="slide-up">
                    <span class="anim-icon">⬆️</span>
                    <strong>slide-up</strong>
                    <span>Slide from below</span>
                </div>
                <div class="anim-card" nx-animate="zoom-in">
                    <span class="anim-icon">🔍</span>
                    <strong>zoom-in</strong>
                    <span>Scale from 85%</span>
                </div>
                <div class="anim-card" nx-animate="slide-left">
                    <span class="anim-icon">⬅️</span>
                    <strong>slide-left</strong>
                    <span>Slide from right</span>
                </div>
                <div class="anim-card" nx-animate-repeat="bounce:2000">
                    <span class="anim-icon">🏀</span>
                    <strong>bounce</strong>
                    <span>Repeats every 2s</span>
                </div>
                <div class="anim-card" nx-animate-repeat="pulse:1500">
                    <span class="anim-icon">💓</span>
                    <strong>pulse</strong>
                    <span>Repeats every 1.5s</span>
                </div>
                <div class="anim-card" nx-animate-repeat="shake:3000">
                    <span class="anim-icon">📳</span>
                    <strong>shake</strong>
                    <span>Repeats every 3s</span>
                </div>
                <div class="anim-card" nx-animate-scroll="slide-up:duration=800">
                    <span class="anim-icon">👁️</span>
                    <strong>scroll-trigger</strong>
                    <span>Fires on viewport enter</span>
                </div>
            </div>
            <div class="anim-info">
                <code>nx-animate="fade-in"</code> ·
                <code>nx-animate-scroll="slide-up:duration=600"</code> ·
                <code>nx-animate-repeat="bounce:2000"</code>
            </div>
        </div>
    </div>

    <div nx-route="/portal" data-nexph-component="portal" style="display:none">
        <div class="portal-view">
            <h2>nx-portal Demo</h2>
            <p class="portal-subtitle">Teleport elements outside the component root</p>
            <div class="portal-demo">
                <div class="portal-card">
                    <h3>📍 Original Position</h3>
                    <p>The tooltip below is defined here in the component tree, but rendered directly on <code>&lt;body&gt;</code> via <code>nx-portal</code>.</p>
                    <button class="btn btn-primary" nx-click="toggleTooltip" style="margin-top:14px">Toggle Portal Tooltip</button>
                </div>
                <div class="portal-card">
                    <h3>💡 How it works</h3>
                    <p style="font-size:13px;color:#64748b;line-height:1.7">
                        Add <code style="color:#a5b4fc">nx-portal</code> to any element.
                        NEXPH moves it to <code style="color:#a5b4fc">document.body</code> (or a custom target)
                        and leaves a comment placeholder at the original position.
                        Perfect for modals, tooltips, and dropdowns.
                    </p>
                </div>
            </div>
            <div nx-if="showTooltip" nx-portal style="position:fixed;bottom:80px;right:80px;z-index:9999;background:#1e1e3a;border:1px solid rgba(99,102,241,0.4);color:#a5b4fc;padding:12px 18px;border-radius:10px;font-size:13px;box-shadow:0 4px 24px rgba(0,0,0,0.5)">
                🪟 I am rendered on &lt;body&gt; via nx-portal!
            </div>
        </div>
    </div>

</div>
HTML;
    }
}
