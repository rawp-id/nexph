# Nexph Root Migration Plan

Tujuan: mengeluarkan isi penting dari `backend-engine/` dan `frontend-engine/` ke root workspace agar root menjadi master runtime repo. Struktur baru harus terlihat seperti runtime/framework distribution, bukan dua repo app yang ditaruh di dalam folder.

## Principles

- Root adalah master Nexph runtime repo
- Tidak pakai `core/Backend`
- Tidak pakai `core/Frontend`
- Backend/runtime core masuk ke `bags/runtime/nexph-core/src/`
- Frontend/UI/compiler runtime masuk ke `bags/runtime/nexph-ui/src/`
- CLI utama ada di `bin/nexph`
- Loader dan package manager masuk ke `src/Runtime`
- Bootstrap runtime masuk ke `runtime/`
- Package bawaan atau calon package masuk ke `bags/local/`
- Demo/app code masuk ke `examples/`
- Generated/runtime data tidak ikut publish
- Migrasi bertahap, tidak destructive

## Target Root Structure

```text
nexph/
  bin/
    nexph
  src/
    Runtime/
      Loader/
      Package/
      CLI/
      Queue/
      Scheduler/
      Supervisor/
      Observability/
    Server/
    Http/
    Database/
    Queue/
    Event/
    Health/
    Log/
    Support/
  ui/
    Runtime/
    Compiler/
    Builder/
    DevServer/
    DevTools/
    Plugin/
    Autoload/
  bags/
    local/
      auth/
      cache/
      generator/
      ui/
    installed/
    cache/
  runtime/
    server/
    fpm/
    workers/
  config/
  database/
  docs/
  examples/
    backend/
    frontend/
    fullstack/
    bags/
  scripts/
    benchmarks/
    smoke/
  tests/
    runtime/
    server/
    http/
    ui/
    bags/
  modules/
  nexph.json
  nexph.lock
  README.md
  ROADMAP.md
  LOADER_PLAN.md
  REPO_CLEANUP_PLAN.md
  ROOT_MIGRATION_PLAN.md
```

## Do Not Do Yet

- [ ] Jangan hapus `backend-engine/`
- [ ] Jangan hapus `frontend-engine/`
- [ ] Jangan hapus nested `.git`
- [ ] Jangan rename namespace besar dulu
- [ ] Jangan pindah Auth/Cache/UI ke package final dulu
- [ ] Jangan ubah public API runtime dulu
- [ ] Jangan ubah command benchmark dulu sebelum wrapper siap
- [ ] Jangan publish sebelum smoke test root runtime lolos

## Phase 0 - Snapshot

- [x] Pastikan root git clean
- [x] Pastikan `backend-engine` git clean
- [x] Pastikan `frontend-engine` git clean
- [x] Catat commit hash root
- [x] Catat commit hash backend
- [x] Catat commit hash frontend
- [x] Catat file count backend
- [x] Catat file count frontend
- [x] Catat current benchmark commands
- [x] Catat current server commands
- [x] Catat current docs entrypoints

## Phase 1 - Create Root Skeleton

- [x] Buat `bin/`
- [x] Buat `bags/runtime/nexph-core/src/`
- [x] Buat `src/Runtime/`
- [x] Buat `src/Runtime/Loader/`
- [x] Buat `src/Runtime/Package/`
- [x] Buat `src/Server/`
- [x] Buat `src/Http/`
- [x] Buat `src/Database/`
- [x] Buat `src/Queue/`
- [x] Buat `src/Event/`
- [x] Buat `src/Health/`
- [x] Buat `src/Log/`
- [x] Buat `src/Support/`
- [x] Buat `bags/runtime/nexph-ui/src/`
- [x] Buat `runtime/`
- [x] Buat `runtime/server/`
- [x] Buat `runtime/fpm/`
- [x] Buat `runtime/workers/`
- [x] Buat `bags/local/`
- [x] Buat `bags/cache/`
- [x] Buat `modules/`
- [x] Buat `bags/installed/`
- [x] Buat `scripts/benchmarks/`
- [x] Buat `scripts/smoke/`
- [x] Buat `examples/backend/`
- [x] Buat `examples/frontend/`
- [x] Buat `examples/fullstack/`
- [x] Buat `examples/bags/`
- [x] Buat `tests/runtime/`
- [x] Buat `tests/server/`
- [x] Buat `tests/http/`
- [x] Buat `tests/ui/`
- [x] Buat `tests/bags/`

## Phase 2 - Backend Runtime Mapping

| From | To | Notes |
|------|----|-------|
| `backend-engine/core/Runtime/` | `src/Runtime/` | merge, keep subfolders |
| `backend-engine/core/Server/` | `src/Server/` | HTTP/WS/SSE server runtime |
| `backend-engine/core/Http/` | `src/Http/` | stateless FPM HTTP layer |
| `backend-engine/core/Database/` | `src/Database/` | core DB layer |
| `backend-engine/core/Queue/` | `src/Queue/` | queue API/runtime |
| `backend-engine/core/Event/` | `src/Event/` | event dispatcher |
| `backend-engine/core/Health/` | `src/Health/` | health checks |
| `backend-engine/core/Log/` | `src/Log/` | logging |
| `backend-engine/core/Support/` | `src/Support/` | config/support |

Tasks:

- [x] Copy `core/Runtime` to `src/Runtime`
- [x] Copy `core/Server` to `src/Server`
- [x] Copy `core/Http` to `src/Http`
- [x] Copy `core/Database` to `src/Database`
- [x] Copy `core/Queue` to `src/Queue`
- [x] Copy `core/Event` to `src/Event`
- [x] Copy `core/Health` to `src/Health`
- [x] Copy `core/Log` to `src/Log`
- [x] Copy `core/Support` to `src/Support`
- [x] Do not delete old backend files
- [x] Run PHP lint on copied runtime files
- [x] Compare file counts

## Phase 3 - Backend Package Candidate Mapping

| From | To | Notes |
|------|----|-------|
| `backend-engine/core/Auth/` | `bags/local/auth/src/` | package candidate |
| `backend-engine/core/Cache/` | `bags/local/cache/src/` | package candidate |
| `backend-engine/core/Generator/` | `bags/local/generator/src/` | package candidate |
| `backend-engine/core/UI/` | `bags/local/ui/src/` | package candidate or bridge |

Tasks:

- [x] Copy Auth package candidate
- [x] Copy Cache package candidate
- [x] Copy Generator package candidate
- [x] Copy UI package candidate
- [ ] Add placeholder package manifests later
- [x] Do not wire package loader yet
- [x] Do not delete old core package candidates

## Phase 4 - Frontend Runtime Mapping

| From | To | Notes |
|------|----|-------|
| `frontend-engine/src/Runtime/` | `ui/Runtime/` | frontend runtime |
| `frontend-engine/src/Compiler/` | `ui/Compiler/` | compiler |
| `frontend-engine/src/Builder/` | `ui/Builder/` | builder |
| `frontend-engine/src/DevServer/` | `ui/DevServer/` | dev server |
| `frontend-engine/src/DevTools/` | `ui/DevTools/` | devtools |
| `frontend-engine/src/Plugin/` | `ui/Plugin/` | plugin system |
| `frontend-engine/src/Autoload/` | `ui/Autoload/` | frontend autoload |
| `frontend-engine/src/Component.php` | `ui/Component.php` | component base |
| `frontend-engine/src/Builder.php` | `ui/Builder.php` | facade |

Tasks:

- [x] Copy frontend runtime to `bags/runtime/nexph-ui/src/`
- [x] Copy compiler to `ui/Compiler`
- [x] Copy builder to `ui/Builder`
- [x] Copy dev server to `ui/DevServer`
- [x] Copy devtools to `ui/DevTools`
- [x] Copy plugin system to `ui/Plugin`
- [x] Copy frontend autoload to `ui/Autoload`
- [x] Copy root frontend classes to `bags/runtime/nexph-ui/src/`
- [x] Run PHP lint on copied UI files
- [x] Compare file counts

## Phase 5 - Bootstrap Mapping

| From | To | Notes |
|------|----|-------|
| `backend-engine/serve.php` | `runtime/server/serve.php` | stateful HTTP/WS/SSE bootstrap |
| `backend-engine/public/index.php` | `runtime/fpm/index.php` | stateless FPM bridge |
| `backend-engine/worker.php` | `runtime/workers/worker.php` | queue worker |
| `backend-engine/worker-daemon.php` | `runtime/workers/worker-daemon.php` | daemon worker |

Tasks:

- [x] Copy `serve.php`
- [x] Copy FPM index
- [x] Copy worker entry
- [x] Copy worker daemon entry
- [x] Update relative autoload paths in copied files
- [x] Do not change old files yet
- [x] Run syntax check

## Phase 6 - CLI Mapping

| From | To | Notes |
|------|----|-------|
| `backend-engine/bin/nexph` | `bin/nexph` | main CLI base |
| `frontend-engine/bin/nexph` | review | merge commands into main CLI |

Tasks:

- [x] Copy backend CLI to `bin/nexph`
- [x] Fix autoload path to root autoload
- [ ] Review frontend CLI commands
- [ ] Design command namespace for frontend commands
- [x] Add `nexph run` plan
- [x] Add `nexph serve` plan
- [x] Add `nexph install` plan
- [ ] Add `nexph build` plan
- [ ] Add `nexph dev` plan
- [x] Keep frontend CLI as reference until merge complete

## Phase 7 - Config And Database Mapping

| From | To | Notes |
|------|----|-------|
| `backend-engine/config/` | `config/` | runtime config |
| `backend-engine/database/` | `database/` | migrations |

Tasks:

- [x] Copy config files
- [x] Copy database migrations
- [ ] Move deploy configs to docs later
- [ ] Review `config/session.php` as package candidate
- [ ] Review `config/api.json` vs `config/api.php`
- [x] Do not copy `.env`
- [x] Copy `.env.example`

## Phase 8 - Docs Mapping

| From | To | Notes |
|------|----|-------|
| `backend-engine/docs/runtime/` | `docs/runtime/` | runtime docs |
| `backend-engine/docs/arsitektur-stateful.md` | `docs/runtime/stateful.md` | rename later |
| `backend-engine/docs/HTTP_SERVER.md` | `docs/runtime/http-server.md` | rename later |
| `backend-engine/docs/RUNTIME_STATEFUL.md` | `docs/runtime/stateful-legacy.md` | review |
| `frontend-engine/docs/` | `docs/ui/` | UI source docs |
| `frontend-engine/docs-site/` | review | generated or hosted docs |

Tasks:

- [x] Copy backend runtime docs
- [x] Copy frontend docs
- [x] Do not copy backend `docs/node_modules`
- [x] Do not copy docs cache
- [x] Do not copy docs dist unless publish decision says yes
- [ ] Merge duplicate docs later
- [x] Keep root plans

## Phase 9 - Scripts Mapping

| From | To |
|------|----|
| `backend-engine/scripts/k6-users.js` | `scripts/benchmarks/k6-users.js` |
| `backend-engine/scripts/k6-ws.js` | `scripts/benchmarks/k6-ws.js` |
| `backend-engine/scripts/k6-sse.js` | `scripts/benchmarks/k6-sse.js` |
| `backend-engine/scripts/server-test.php` | `scripts/smoke/server-test.php` |
| `backend-engine/scripts/ws-test.php` | `scripts/smoke/ws-test.php` |
| `backend-engine/scripts/memory-test.php` | `scripts/smoke/memory-test.php` |
| `backend-engine/verify-runtime.sh` | `scripts/smoke/verify-runtime.sh` |

Tasks:

- [x] Copy benchmark scripts
- [x] Copy smoke scripts
- [ ] Update paths if needed
- [x] Keep old scripts until root commands work

## Phase 10 - Examples Mapping

| From | To |
|------|----|
| `backend-engine/examples/` | `examples/backend/` |
| `backend-engine/app/` | `examples/backend/app/` |
| `backend-engine/routes/` | `examples/backend/routes/` |
| `backend-engine/metadata/` | `examples/backend/data/` |
| `frontend-engine/examples/` | `examples/frontend/` |

Tasks:

- [x] Copy backend examples
- [x] Copy backend demo app
- [x] Copy backend demo routes
- [x] Copy backend metadata
- [x] Copy frontend examples
- [ ] Add examples README later

## Phase 11 - Tests Mapping

| From | To |
|------|----|
| `backend-engine/tests/Runtime/` | `tests/runtime/` |
| `backend-engine/tests/Unit/` | `tests/runtime/unit/` |
| `backend-engine/tests/queue_*.php` | `tests/runtime/queue/` |
| `frontend-engine/tests/` | `tests/ui/` |

Tasks:

- [x] Copy backend runtime tests
- [x] Copy backend unit tests
- [x] Copy queue tests
- [x] Copy frontend tests
- [x] Do not copy generated benchmark results
- [ ] Update test bootstrap paths later

## Phase 12 - Root Autoload Plan

- [x] Create root `autoload.php`
- [x] Map `Core\Runtime` to `src/Runtime`
- [x] Map `Core\Server` to `src/Server`
- [x] Map `Core\Http` to `src/Http`
- [x] Map `Core\Database` to `src/Database`
- [x] Map `Core\Queue` to `src/Queue`
- [x] Map `Core\Event` to `src/Event`
- [x] Map `Core\Health` to `src/Health`
- [x] Map `Core\Log` to `src/Log`
- [x] Map `Core\Support` to `src/Support`
- [x] Map `Nexph` UI namespace to `bags/runtime/nexph-ui/src/`
- [x] Add package loader later
- [x] Keep Composer bridge optional
- [x] Test class autoload

## Phase 13 - Root Manifest Plan

- [x] Create root `nexph.json`
- [x] Add package name
- [x] Add version
- [x] Add runtime section
- [x] Add autoload section
- [x] Add package manager section
- [x] Add repositories section
- [x] Add scripts section
- [x] Do not create `nexph.lock` until resolver works

## Phase 14 - Verification

- [x] PHP lint root copied backend files
- [x] PHP lint root copied UI files
- [x] Test root autoload
- [x] Test root CLI basic command
- [ ] Test root `runtime/server/serve.php --help` later
- [ ] Test root stateless FPM bootstrap syntax
- [x] Test root smoke script syntax
- [x] Compare old and new file counts
- [x] Confirm old engine folders untouched

## Phase 15 - Switch Over Later

- [ ] Implement root `bin/nexph`
- [ ] Implement root `nexph run`
- [ ] Implement root `nexph serve`
- [ ] Implement root loader
- [ ] Implement root package manager
- [ ] Run root HTTP smoke
- [ ] Run root WS smoke
- [ ] Run root SSE smoke
- [ ] Update README root
- [ ] Mark backend/frontend old dirs as archived
- [ ] Remove old dirs only after release branch is safe

