# Nexph Runtime Foundation Roadmap

# 1. Runtime Kernel 

Core paling bawah. Semua hidup dari sini.

## Core Event System

* [ ] Event loop abstraction
* [ ] Deferred task queue
* [ ] Microtask queue
* [ ] Tick scheduler
* [ ] Timer system
* [ ] Idle callback scheduler
* [ ] Signal handling
* [ ] Graceful shutdown lifecycle

## Worker Runtime

* [ ] Worker bootstrap
* [ ] Worker supervisor
* [ ] Worker restart strategy
* [ ] Worker generations
* [ ] Worker drain mode
* [ ] Cross-worker communication
* [ ] Shared metrics aggregation
* [ ] Runtime hot reload research

## Memory & Lifecycle

* [ ] Ownership model
* [ ] Borrowed object lease
* [ ] Lifecycle contracts
* [ ] Cleanup contracts
* [ ] Pool safety guard
* [ ] Context contamination detection
* [ ] WeakMap retained object tracking
* [ ] Runtime leak audit mode
* [ ] GC pressure metrics
* [ ] Memory pressure mode

---

# 2. Runtime Foundation 

Layer abstraction untuk semua runtime mode.

## Runtime Modes

* [ ] Stateless runtime
* [ ] Stateful runtime
* [ ] Realtime runtime
* [ ] Streaming runtime
* [ ] Hybrid runtime mode

## Scheduler Isolation

* [ ] REST scheduler
* [ ] WS scheduler
* [ ] SSE scheduler
* [ ] Background task scheduler
* [ ] Priority queue scheduling
* [ ] Fair scheduling
* [ ] Runtime QoS

## Backpressure System

* [ ] Connection backpressure
* [ ] Stream backpressure
* [ ] Queue pressure handling
* [ ] Adaptive throttling
* [ ] Slow consumer detection
* [ ] Auto drop strategy
* [ ] Runtime pressure controller

---

# 3. HTTP Runtime 

## HTTP Core

* [ ] HTTP parser
* [ ] Router
* [ ] Middleware pipeline
* [ ] Route groups
* [ ] Context/request container
* [ ] Streaming response
* [ ] Chunked transfer
* [ ] Keep-alive management
* [ ] Compression
* [ ] Static file serving

## HTTP Performance

* [ ] Route cache
* [ ] Response pooling
* [ ] Header optimization
* [ ] Fast path responses
* [ ] Zero-copy experiments
* [ ] APCu route metadata cache

---

# 4. WebSocket Runtime 

## WS Core

* [ ] WS handshake
* [ ] Frame parser
* [ ] Connection manager
* [ ] Channel/pubsub system
* [ ] Broadcast engine
* [ ] Heartbeat/ping system
* [ ] Disconnect cleanup

## WS Scaling

* [ ] Cross-worker WS bus
* [ ] WS backpressure
* [ ] Slow socket isolation
* [ ] Message batching
* [ ] Topic partitioning
* [ ] Distributed WS gateway research

---

# 5. SSE Runtime 

## SSE Core

* [ ] Event stream manager
* [ ] Heartbeat system
* [ ] Stream cleanup
* [ ] Chunk flushing
* [ ] Retry support
* [ ] Event id support

## SSE Isolation

* [ ] SSE scheduler
* [ ] Stream buffer control
* [ ] Slow stream detection
* [ ] SSE broadcast channels

---

# 6. Runtime Observability 

## Metrics

* [ ] HTTP metrics
* [ ] WS metrics
* [ ] SSE metrics
* [ ] Runtime loop lag
* [ ] Worker metrics
* [ ] Queue metrics
* [ ] Pool metrics
* [ ] Ownership metrics
* [ ] Leak metrics

## Diagnostics

* [ ] Runtime inspector
* [ ] Connection inspector
* [ ] Object tracker dashboard
* [ ] Runtime profiling mode
* [ ] Debug tracing
* [ ] Runtime snapshots

---

# 7. Runtime Loader & Module System 

## Native Loader

* [ ] Nexph autoloader
* [ ] Runtime preload
* [ ] Lazy module resolver
* [ ] Runtime package metadata
* [ ] Module registry

## Composer Bridge

* [ ] Composer compatibility layer
* [ ] Composer package resolver
* [ ] Hybrid package loading

## Runtime Packages

* [ ] Native Nexph package format
* [ ] Runtime package manifest
* [ ] Package sandbox research

---

# 8. Framework Layer 

Express-like philosophy 

## Minimal API

* [ ] Route API
* [ ] Middleware API
* [ ] Context API
* [ ] Runtime hooks
* [ ] Event hooks
* [ ] Plugin system

## Flexible Patterns

* [ ] MVC optional
* [ ] Functional routing
* [ ] Modular apps
* [ ] Service container optional
* [ ] Event-driven modules

---

# 9. Developer Experience 

## CLI

* [ ] `nexph serve`
* [ ] `nexph ws:start`
* [ ] `nexph runtime:stats`
* [ ] `nexph worker:list`
* [ ] `nexph inspect`
* [ ] `nexph benchmark`

## Dev Tools

* [ ] Runtime dashboard
* [ ] Live metrics UI
* [ ] Hot reload experiments
* [ ] Runtime tracing
* [ ] Debug mode

---

# 10. Long-Term Research 

## Advanced Runtime

* [ ] Fiber scheduler optimization
* [ ] Runtime JIT experiments
* [ ] Async filesystem
* [ ] Async DB drivers
* [ ] Runtime snapshots
* [ ] Distributed runtime clustering
* [ ] Runtime-native queue engine

## Native Experiments

* [ ] Rust sidecar runtime
* [ ] Native protocol parser
* [ ] eBPF observability experiments
* [ ] QUIC/TUIC research
* [ ] Runtime binary packaging

---

# Suggested Priority 

| Priority | Focus                       |
| -------- | --------------------------- |
| P0     | runtime kernel + lifecycle  |
| P1     | HTTP/WS/SSE stable runtime  |
| P2     | observability + diagnostics |
| P3     | loader/module ecosystem     |
| P4     | framework layer             |
| P5     | distributed/native research |

---

# 11. Nexph UI / FE Runtime 

## Frontend Runtime Core

* [ ] Reactive state engine
* [ ] Signal system
* [ ] Fine-grained reactivity
* [ ] Component lifecycle
* [ ] Effect scheduler
* [ ] DOM renderer
* [ ] Diffing strategy research
* [ ] Event delegation

---

# 12. Universal Data Bridge 

## Runtime Data Bridge

* [ ] Runtime state sync
* [ ] Server signal sync
* [ ] Shared reactive store
* [ ] Direct WS state bridge
* [ ] SSE reactive stream
* [ ] Auto hydration
* [ ] Server action system
* [ ] Live object serialization
* [ ] Realtime UI binding

---

# 13. Nexph UI Framework 

## UI Layer

* [ ] JSX-like syntax research
* [ ] Template compiler
* [ ] Native component system
* [ ] SSR support
* [ ] Streaming SSR
* [ ] Islands architecture
* [ ] Partial hydration
* [ ] Edge rendering research

## UI Runtime

* [ ] Client runtime
* [ ] Shared server/client context
* [ ] Runtime navigation
* [ ] Reactive router
* [ ] Suspense-like async boundary

---

# 14. Realtime UI 

## Native Realtime

* [ ] Live state sync
* [ ] Realtime DOM patch
* [ ] Built-in WS/SSE bridge
* [ ] Shared runtime events
* [ ] Reactive streaming UI

---

# 15. Nexph Fullstack Runtime 

## Fullstack Runtime Vision

* [ ] Shared contracts FE/BE
* [ ] Unified routing
* [ ] Shared validation
* [ ] Shared runtime context
* [ ] Shared event system
* [ ] End-to-end reactive pipeline
