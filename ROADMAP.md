# Nexph Runtime Foundation Roadmap

# 1. Runtime Kernel 

Core paling bawah. Semua hidup dari sini.

## Core Event System

* [x] Event loop abstraction
* [x] Deferred task queue
* [ ] Microtask queue
* [x] Tick scheduler
* [x] Timer system
* [ ] Idle callback scheduler
* [x] Signal handling
* [x] Graceful shutdown lifecycle

## Worker Runtime

* [x] Worker bootstrap
* [x] Worker supervisor
* [x] Worker restart strategy
* [ ] Worker generations
* [x] Worker drain mode
* [x] Cross-worker communication
* [x] Shared metrics aggregation
* [ ] Runtime hot reload research

## Memory & Lifecycle

* [x] Ownership model
* [x] Borrowed object lease
* [x] Lifecycle contracts
* [x] Cleanup contracts
* [x] Pool safety guard
* [x] Context contamination detection
* [x] WeakMap retained object tracking
* [x] Runtime leak audit mode
* [ ] GC pressure metrics
* [x] Memory pressure mode

---

# 2. Runtime Foundation 

Layer abstraction untuk semua runtime mode.

## Runtime Modes

* [x] Stateless runtime
* [x] Stateful runtime
* [x] Realtime runtime
* [x] Streaming runtime
* [x] Hybrid runtime mode

## Scheduler Isolation

* [x] REST scheduler
* [x] WS scheduler
* [x] SSE scheduler
* [ ] Background task scheduler
* [ ] Priority queue scheduling
* [ ] Fair scheduling
* [ ] Runtime QoS

## Backpressure System

* [x] Connection backpressure
* [x] Stream backpressure
* [x] Queue pressure handling
* [x] Adaptive throttling
* [x] Slow consumer detection
* [x] Auto drop strategy
* [x] Runtime pressure controller

---

# 3. HTTP Runtime 

## HTTP Core

* [x] HTTP parser
* [x] Router
* [x] Middleware pipeline
* [x] Route groups
* [x] Context/request container
* [x] Streaming response
* [ ] Chunked transfer
* [x] Keep-alive management
* [x] Compression
* [x] Static file serving

## HTTP Performance

* [ ] Route cache
* [x] Response pooling
* [x] Header optimization
* [x] Fast path responses
* [ ] Zero-copy experiments
* [ ] APCu route metadata cache

---

# 4. WebSocket Runtime 

## WS Core

* [x] WS handshake
* [x] Frame parser
* [x] Connection manager
* [x] Channel/pubsub system
* [x] Broadcast engine
* [x] Heartbeat/ping system
* [x] Disconnect cleanup

## WS Scaling

* [x] Cross-worker WS bus
* [x] WS backpressure
* [x] Slow socket isolation
* [x] Message batching
* [x] Topic partitioning
* [ ] Distributed WS gateway research

---

# 5. SSE Runtime 

## SSE Core

* [x] Event stream manager
* [x] Heartbeat system
* [x] Stream cleanup
* [x] Chunk flushing
* [x] Retry support
* [x] Event id support

## SSE Isolation

* [x] SSE scheduler
* [x] Stream buffer control
* [x] Slow stream detection
* [x] SSE broadcast channels

---

# 6. Runtime Observability 

## Metrics

* [x] HTTP metrics
* [x] WS metrics
* [x] SSE metrics
* [x] Runtime loop lag
* [x] Worker metrics
* [x] Queue metrics
* [x] Pool metrics
* [x] Ownership metrics
* [x] Leak metrics

## Diagnostics

* [x] Runtime inspector
* [ ] Connection inspector
* [ ] Object tracker dashboard
* [ ] Runtime profiling mode
* [x] Debug tracing
* [ ] Runtime snapshots

---

# 7. Runtime Loader & Module System 

## Native Loader

* [x] Nexph autoloader
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

* [x] Route API
* [x] Middleware API
* [x] Context API
* [x] Runtime hooks
* [x] Event hooks
* [ ] Plugin system

## Flexible Patterns

* [x] MVC optional
* [x] Functional routing
* [x] Modular apps
* [ ] Service container optional
* [x] Event-driven modules

---

# 9. Developer Experience 

## CLI

* [ ] `nexph serve`
* [ ] `nexph ws:start`
* [x] `nexph runtime:stats`
* [x] `nexph worker:list`
* [ ] `nexph inspect`
* [ ] `nexph benchmark`

## Dev Tools

* [x] Runtime dashboard
* [x] Live metrics UI
* [x] Hot reload experiments
* [ ] Runtime tracing
* [x] Debug mode

---

# 10. Long-Term Research 

## Advanced Runtime

* [ ] Fiber scheduler optimization
* [ ] Runtime JIT experiments
* [x] Async filesystem
* [ ] Async DB drivers
* [ ] Runtime snapshots
* [ ] Distributed runtime clustering
* [x] Runtime-native queue engine

## Native Experiments

* [ ] Rust sidecar runtime
* [ ] Native protocol parser
* [ ] eBPF observability experiments
* [ ] QUIC/TUIC research
* [ ] Runtime binary packaging

---

# 11. Nexph UI / FE Runtime 

## Frontend Runtime Core

* [x] Reactive state engine
* [ ] Signal system
* [ ] Fine-grained reactivity
* [x] Component lifecycle
* [x] Effect scheduler
* [x] DOM renderer
* [ ] Diffing strategy research
* [x] Event delegation

---

# 12. Universal Data Bridge 

## Runtime Data Bridge

* [ ] Runtime state sync
* [ ] Server signal sync
* [x] Shared reactive store
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
* [x] Template compiler
* [x] Native component system
* [x] SSR support
* [ ] Streaming SSR
* [ ] Islands architecture
* [ ] Partial hydration
* [ ] Edge rendering research

## UI Runtime

* [x] Client runtime
* [ ] Shared server/client context
* [x] Runtime navigation
* [x] Reactive router
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
