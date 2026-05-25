# NEXPH UI — Frontend Framework Pivot PRD

## “Modern Frontend Framework Powered by PHP”

---

# 1. Executive Summary

NEXPH UI adalah pivot besar dari NEXPH menjadi:

```txt id="w7j9io"
Frontend-first PHP framework ecosystem
```

NEXPH UI memungkinkan developer membangun frontend modern menggunakan PHP component, kemudian dibuild menjadi:

```txt id="cavf8t"
HTML
CSS
Tiny JS Runtime
Hydration Manifest
```

dengan arsitektur:

```txt id="crg4u0"
SSR-first
HTML-first
Realtime-ready
Low-bandwidth optimized
```

Target utama:

```txt id="6ggr7n"
React-like developer experience
tanpa harus meninggalkan PHP ecosystem.
```

---

# 2. Strategic Pivot

## Previous Direction

```txt id="j3uj38"
General-purpose PHP framework/runtime
```

---

## New Direction

```txt id="15j3q3"
Modern frontend framework powered by PHP
```

---

# 3. Product Vision

```txt id="3l8t0x"
Enable PHP developers to create modern frontend applications
using PHP as the primary UI language.
```

---

# 4. Core Philosophy

## A. PHP-first

Frontend dibuat menggunakan PHP.

---

## B. HTML-first

Output utama adalah:

```txt id="wjlwmj"
real HTML
```

bukan virtual DOM-heavy architecture.

---

## C. Compile-time Framework

NEXPH UI adalah:

```txt id="7sukup"
compile-time frontend framework
```

bukan runtime-heavy SPA framework.

---

## D. Minimal JavaScript

JavaScript hanya digunakan sebagai:

```txt id="mjlwm5"
event bridge
hydration runtime
DOM patch runtime
```

---

## E. Realtime-native

Realtime bukan addon.

Realtime adalah arsitektur inti.

---

# 5. Problem Statement

Frontend modern saat ini mengalami:

* hydration berat
* tooling kompleks
* JS ecosystem terlalu fragmented
* bundle size besar
* SSR sulit
* low-end device struggle
* bandwidth tinggi
* backend/frontend context switching

Sementara developer PHP:

```txt id="0l5o7r"
besar secara global
tetapi belum memiliki frontend ecosystem modern sendiri
```

---

# 6. Product Goals

## Primary Goals

### A. Modern frontend with PHP

```txt id="x0g8on"
PHP menjadi bahasa frontend modern
```

---

### B. Buildable frontend

Support:

```bash id="c8vq4v"
nexph build
```

menghasilkan production assets.

---

### C. Faster-than-React architecture (specific scenarios)

NEXPH UI ditargetkan lebih cepat dari React pada:

* SSR pages
* marketplace
* content-heavy apps
* low-bandwidth apps
* mobile-first apps
* realtime server-driven UI

Karena:

* HTML-first
* minimal hydration
* tiny runtime
* no heavy virtual DOM

---

### D. Low-bandwidth optimized

Cocok untuk:

* Indonesia
* low-end smartphone
* unstable network

---

### E. Realtime-ready

Support:

* websocket
* live state
* DOM diff patch

---

# 7. Target Users

## Primary

* PHP developers
* Laravel developers
* Webman developers
* backend developers

---

## Secondary

* startup MVP teams
* indie hackers
* agencies
* realtime dashboard builders
* low-bandwidth app developers

---

# 8. Core Concept

Developer membuat frontend menggunakan PHP component.

---

## Example Component

```php id="d3qv9r"
class Counter extends Component
{
    public int $count = 0;

    public function increment()
    {
        $this->count++;
    }

    public function render()
    {
        return <>
            <div class="card">
                <h1>{$this->count}</h1>

                <button onClick={$this->increment}>
                    Tambah
                </button>
            </div>
        </>;
    }
}
```

---

# 9. Build System

Command:

```bash id="q4ub6w"
nexph build
```

Output:

```txt id="vdj9v8"
dist/
├─ index.html
├─ app.js
├─ app.css
└─ manifest.json
```

---

# 10. Rendering Modes

## A. Static Build Mode

Output:

```txt id="r8rjlwm"
Pure static HTML
```

Use case:

* landing page
* blog
* documentation
* catalog

Deployment:

* CDN
* Cloudflare Pages
* Netlify
* Vercel
* static hosting

Tidak membutuhkan runtime PHP aktif.

---

## B. Hybrid Mode

Sebagian static, sebagian reactive.

Contoh:

```php id="r9mykl"
<Feed live />
```

---

## C. Full Live Mode

Mirip:

* Phoenix LiveView
* Hotwire

Architecture:

```txt id="kg2iho"
WebSocket
+
server state
+
DOM patch
```

Use case:

* realtime feed
* chat
* dashboard
* collaborative apps

---

# 11. Architecture

## High-Level Architecture

```txt id="5g2j57"
PHP Component
        ↓
AST Parser
        ↓
Compiler Engine
        ├─ HTML Generator
        ├─ CSS Extractor
        ├─ JS Runtime Generator
        └─ Hydration Manifest
```

Browser runtime:

```txt id="90xzvj"
Browser
 ↓
Tiny JS Runtime
 ↓
Event/WebSocket Bridge
 ↓
NEXPH Runtime
```

---

# 12. Runtime Strategy

## Initial Runtime

```txt id="qjlwmh"
Webman
```

Karena:

* worker-based
* async-ready
* websocket-native
* fast runtime

---

## Future Runtime Support

* FrankenPHP
* OpenSwoole
* RoadRunner

---

# 13. Technical Philosophy

## Server-driven UI

```txt id="2gmwej"
State utama berada di server
```

---

## Tiny Client Runtime

Target:

```txt id="jlwmga"
<30kb runtime
```

---

## Minimal Hydration

Hydration hanya dilakukan bila diperlukan.

---

## HTML Streaming

Support:

* streamed SSR
* partial rendering
* incremental rendering

---

# 14. Core Features

## 14.1 Component System

Features:

* props
* slots
* nested component
* conditional rendering
* loops

---

## 14.2 Reactive State

```php id="5e6x5n"
$this->count++;
```

otomatis update UI.

---

## 14.3 Event Binding

```php id="tf7z3t"
<button nx-click="increment">
```

Supported:

* click
* input
* submit
* keyboard
* custom event

---

## 14.4 Scoped CSS

```php id="u2p3p3"
<style scoped>
.card {
    border-radius: 20px;
}
</style>
```

---

## 14.5 Realtime State

```php id="wjlwmr"
State::live('chat-room');
```

---

## 14.6 Tiny Runtime

Client runtime hanya mengurus:

* event
* websocket
* patch update
* hydration

---

# 15. Compiler Strategy

## Phase 1

Pure PHP compiler.

Reason:

* easy contribution
* easy setup
* no native dependency

---

## Phase 2

Optional Rust accelerator.

Used for:

* AST parsing
* HTML diffing
* compiler acceleration
* websocket hub

---

# 16. Why NEXPH UI Can Be Faster Than React

## Faster on:

* SSR-heavy apps
* content-heavy apps
* marketplace
* low-bandwidth apps
* low-end device

---

## Because:

```txt id="jlwmx0"
No heavy hydration
Minimal JS
No large virtual DOM
HTML-first rendering
Tiny runtime
Server-driven updates
```

---

# 17. File Structure

```txt id="jlwmx1"
app/
├─ components/
│  ├─ Button.phpx
│  ├─ Card.phpx
│  └─ Navbar.phpx
│
├─ pages/
│  ├─ Home.phpx
│  └─ Feed.phpx
│
└─ app.php
```

---

# 18. Proposed Syntax

## JSX-like PHP

```php id="jlwmx2"
return <>
    <Card>
        <h1>Hello World</h1>
    </Card>
</>;
```

---

# 19. MVP Scope

## Week 1

### Component Compiler

Support:

* HTML rendering
* props
* slots

---

## Week 2

### Runtime

Support:

* nx-click
* event binding
* state update

---

## Week 3

### Build Tool

Support:

* static export
* CSS extraction
* runtime bundling

---

## Week 4

### Demo Applications

Build:

* realtime counter
* mini twitter feed
* realtime chat

---

# 20. Long-Term Ecosystem

```txt id="jlwmx3"
NEXPH UI
NEXPH Runtime
NEXPH State
NEXPH Cloud
NEXPH Deploy
NEXPH Mobile
```

---

# 21. Positioning

```txt id="jlwmx4"
The frontend framework for PHP developers.
```

Alternative:

```txt id="jlwmx5"
React experience.
PHP simplicity.
Realtime native.
```

---

# 22. Final Strategic Direction

NEXPH bukan lagi diposisikan sebagai:

```txt id="jlwmx6"
another PHP framework
```

melainkan:

```txt id="jlwmx7"
modern frontend ecosystem powered by PHP
```

dengan fokus utama:

```txt id="jlwmx8"
buildable frontend
SSR-first architecture
realtime-native system
low-bandwidth optimization
PHP-first developer experience
```
