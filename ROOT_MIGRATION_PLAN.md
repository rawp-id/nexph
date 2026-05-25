# Nexph Root Migration Plan

Tujuan: mengeluarkan isi penting dari `backend-engine/` dan `frontend-engine/` ke root workspace agar root menjadi master runtime repo. Struktur baru harus terlihat seperti runtime/framework distribution, bukan dua repo app yang ditaruh di dalam folder.

## Principles

- Root adalah master Nexph runtime repo
- Tidak pakai `core/Backend`
- Tidak pakai `core/Frontend`
- Backend/runtime core masuk ke `src/`
- Frontend/UI/compiler runtime masuk ke `ui/`
- CLI utama ada di `bin/nexph`
- Loader dan package manager masuk ke `src/Runtime`
- Bootstrap runtime masuk ke `runtime/`
- Package bawaan atau calon package masuk ke `packages/`
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
  packages/
    auth/
    cache/
    generator/
    ui/
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
    packages/
  scripts/
    benchmarks/
    smoke/
  tests/
    runtime/
    server/
    http/
    ui/
    packages/
  modules/
  nexph_modules/
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

- [ ] Pastikan root git clean
- [ ] Pastikan `backend-engine` git clean
- [ ] Pastikan `frontend-engine` git clean
- [ ] Catat commit hash root
- [ ] Catat commit hash backend
- [ ] Catat commit hash frontend
- [ ] Catat file count backend
- [ ] Catat file count frontend
- [ ] Catat current benchmark commands
- [ ] Catat current server commands
- [ ] Catat current docs entrypoints

## Phase 1 - Create Root Skeleton

- [ ] Buat `bin/`
- [ ] Buat `src/`
- [ ] Buat `src/Runtime/`
- [ ] Buat `src/Runtime/Loader/`
- [ ] Buat `src/Runtime/Package/`
- [ ] Buat `src/Server/`
- [ ] Buat `src/Http/`
- [ ] Buat `src/Database/`
- [ ] Buat `src/Queue/`
- [ ] Buat `src/Event/`
- [ ] Buat `src/Health/`
- [ ] Buat `src/Log/`
- [ ] Buat `src/Support/`
- [ ] Buat `ui/`
- [ ] Buat `runtime/`
- [ ] Buat `runtime/server/`
- [ ] Buat `runtime/fpm/`
- [ ] Buat `runtime/workers/`
- [ ] Buat `packages/`
- [ ] Buat `modules/`
- [ ] Buat `nexph_modules/`
- [ ] Buat `scripts/benchmarks/`
- [ ] Buat `scripts/smoke/`
- [ ] Buat `examples/backend/`
- [ ] Buat `examples/frontend/`
- [ ] Buat `examples/fullstack/`
- [ ] Buat `examples/packages/`
- [ ] Buat `tests/runtime/`
- [ ] Buat `tests/server/`
- [ ] Buat `tests/http/`
- [ ] Buat `tests/ui/`
- [ ] Buat `tests/packages/`

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

- [ ] Copy `core/Runtime` to `src/Runtime`
- [ ] Copy `core/Server` to `src/Server`
- [ ] Copy `core/Http` to `src/Http`
- [ ] Copy `core/Database` to `src/Database`
- [ ] Copy `core/Queue` to `src/Queue`
- [ ] Copy `core/Event` to `src/Event`
- [ ] Copy `core/Health` to `src/Health`
- [ ] Copy `core/Log` to `src/Log`
- [ ] Copy `core/Support` to `src/Support`
- [ ] Do not delete old backend files
- [ ] Run PHP lint on copied runtime files
- [ ] Compare file counts

## Phase 3 - Backend Package Candidate Mapping

| From | To | Notes |
|------|----|-------|
| `backend-engine/core/Auth/` | `packages/auth/src/` | package candidate |
| `backend-engine/core/Cache/` | `packages/cache/src/` | package candidate |
| `backend-engine/core/Generator/` | `packages/generator/src/` | package candidate |
| `backend-engine/core/UI/` | `packages/ui/src/` | package candidate or bridge |

Tasks:

- [ ] Copy Auth package candidate
- [ ] Copy Cache package candidate
- [ ] Copy Generator package candidate
- [ ] Copy UI package candidate
- [ ] Add placeholder package manifests later
- [ ] Do not wire package loader yet
- [ ] Do not delete old core package candidates

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

- [ ] Copy frontend runtime to `ui/`
- [ ] Copy compiler to `ui/Compiler`
- [ ] Copy builder to `ui/Builder`
- [ ] Copy dev server to `ui/DevServer`
- [ ] Copy devtools to `ui/DevTools`
- [ ] Copy plugin system to `ui/Plugin`
- [ ] Copy frontend autoload to `ui/Autoload`
- [ ] Copy root frontend classes to `ui/`
- [ ] Run PHP lint on copied UI files
- [ ] Compare file counts

## Phase 5 - Bootstrap Mapping

| From | To | Notes |
|------|----|-------|
| `backend-engine/serve.php` | `runtime/server/serve.php` | stateful HTTP/WS/SSE bootstrap |
| `backend-engine/public/index.php` | `runtime/fpm/index.php` | stateless FPM bridge |
| `backend-engine/worker.php` | `runtime/workers/worker.php` | queue worker |
| `backend-engine/worker-daemon.php` | `runtime/workers/worker-daemon.php` | daemon worker |

Tasks:

- [ ] Copy `serve.php`
- [ ] Copy FPM index
- [ ] Copy worker entry
- [ ] Copy worker daemon entry
- [ ] Update relative autoload paths in copied files
- [ ] Do not change old files yet
- [ ] Run syntax check

## Phase 6 - CLI Mapping

| From | To | Notes |
|------|----|-------|
| `backend-engine/bin/nexph` | `bin/nexph` | main CLI base |
| `frontend-engine/bin/nexph` | review | merge commands into main CLI |

Tasks:

- [ ] Copy backend CLI to `bin/nexph`
- [ ] Fix autoload path to root autoload
- [ ] Review frontend CLI commands
- [ ] Design command namespace for frontend commands
- [ ] Add `nexph run` plan
- [ ] Add `nexph serve` plan
- [ ] Add `nexph install` plan
- [ ] Add `nexph build` plan
- [ ] Add `nexph dev` plan
- [ ] Keep frontend CLI as reference until merge complete

## Phase 7 - Config And Database Mapping

| From | To | Notes |
|------|----|-------|
| `backend-engine/config/` | `config/` | runtime config |
| `backend-engine/database/` | `database/` | migrations |

Tasks:

- [ ] Copy config files
- [ ] Copy database migrations
- [ ] Move deploy configs to docs later
- [ ] Review `config/session.php` as package candidate
- [ ] Review `config/api.json` vs `config/api.php`
- [ ] Do not copy `.env`
- [ ] Copy `.env.example`

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

- [ ] Copy backend runtime docs
- [ ] Copy frontend docs
- [ ] Do not copy backend `docs/node_modules`
- [ ] Do not copy docs cache
- [ ] Do not copy docs dist unless publish decision says yes
- [ ] Merge duplicate docs later
- [ ] Keep root plans

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

- [ ] Copy benchmark scripts
- [ ] Copy smoke scripts
- [ ] Update paths if needed
- [ ] Keep old scripts until root commands work

## Phase 10 - Examples Mapping

| From | To |
|------|----|
| `backend-engine/examples/` | `examples/backend/` |
| `backend-engine/app/` | `examples/backend/app/` |
| `backend-engine/routes/` | `examples/backend/routes/` |
| `backend-engine/metadata/` | `examples/backend/data/` |
| `frontend-engine/examples/` | `examples/frontend/` |

Tasks:

- [ ] Copy backend examples
- [ ] Copy backend demo app
- [ ] Copy backend demo routes
- [ ] Copy backend metadata
- [ ] Copy frontend examples
- [ ] Add examples README later

## Phase 11 - Tests Mapping

| From | To |
|------|----|
| `backend-engine/tests/Runtime/` | `tests/runtime/` |
| `backend-engine/tests/Unit/` | `tests/runtime/unit/` |
| `backend-engine/tests/queue_*.php` | `tests/runtime/queue/` |
| `frontend-engine/tests/` | `tests/ui/` |

Tasks:

- [ ] Copy backend runtime tests
- [ ] Copy backend unit tests
- [ ] Copy queue tests
- [ ] Copy frontend tests
- [ ] Do not copy generated benchmark results
- [ ] Update test bootstrap paths later

## Phase 12 - Root Autoload Plan

- [ ] Create root `autoload.php`
- [ ] Map `Core\Runtime` to `src/Runtime`
- [ ] Map `Core\Server` to `src/Server`
- [ ] Map `Core\Http` to `src/Http`
- [ ] Map `Core\Database` to `src/Database`
- [ ] Map `Core\Queue` to `src/Queue`
- [ ] Map `Core\Event` to `src/Event`
- [ ] Map `Core\Health` to `src/Health`
- [ ] Map `Core\Log` to `src/Log`
- [ ] Map `Core\Support` to `src/Support`
- [ ] Map `Nexph` UI namespace to `ui/`
- [ ] Add package loader later
- [ ] Keep Composer bridge optional
- [ ] Test class autoload

## Phase 13 - Root Manifest Plan

- [ ] Create root `nexph.json`
- [ ] Add package name
- [ ] Add version
- [ ] Add runtime section
- [ ] Add autoload section
- [ ] Add package manager section
- [ ] Add repositories section
- [ ] Add scripts section
- [ ] Do not create `nexph.lock` until resolver works

## Phase 14 - Verification

- [ ] PHP lint root copied backend files
- [ ] PHP lint root copied UI files
- [ ] Test root autoload
- [ ] Test root CLI basic command
- [ ] Test root `runtime/server/serve.php --help` later
- [ ] Test root stateless FPM bootstrap syntax
- [ ] Test root smoke script syntax
- [ ] Compare old and new file counts
- [ ] Confirm old engine folders untouched

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

