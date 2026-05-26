# Nexph Runtime Distribution Cleanup Plan

Tujuan: merapikan `backend-engine/` dan `frontend-engine/` menjadi runtime-only distribution yang siap dipublish, lalu menyiapkan fondasi `nexph` CLI seperti Node/Bun/Deno: bisa run file, serve runtime, install package, dan load module.

## Target Identity

- `backend-engine/` adalah Nexph Backend Runtime
- `frontend-engine/` adalah Nexph Frontend Runtime + Compiler
- Root Nexph adalah workspace pengembangan
- Repo publish tidak boleh terasa seperti app demo
- Runtime harus bisa dipakai sebagai engine
- CLI harus menjadi pintu utama developer
- Composer hanya bridge opsional
- Package native Nexph adalah first-class

## Target CLI Vision

```bash
nexph run file.php
nexph serve
nexph serve --mode=http
nexph ws:start
nexph sse:start
nexph install nexph/auth
nexph install composer:monolog/monolog
nexph remove nexph/auth
nexph update
nexph module:list
nexph runtime:stats
nexph benchmark
nexph dev
nexph build
```

## Phase 0 - Freeze

- [x] Stop fitur runtime baru sementara
- [x] Stop transport baru sementara
- [x] Stop UI feature baru sementara
- [x] Fokus cleanup folder
- [x] Fokus docs publish
- [x] Fokus CLI shape
- [x] Fokus loader/package manager plan
- [x] Jangan hapus file sebelum mapping jelas
- [x] Jangan pindah file tanpa test
- [x] Jangan rename namespace besar dulu

## Phase 1 - Workspace Audit

- [x] Audit root `/home/rawp/Tech/nexph`
- [x] Audit root files
- [x] Audit root plans
- [x] Audit root docs
- [x] Audit `backend-engine/`
- [x] Audit `frontend-engine/`
- [x] Audit nested `.git`
- [x] Audit generated files
- [x] Audit benchmark artifacts
- [x] Audit SQLite runtime files
- [x] Audit backup files
- [x] Audit docs build artifacts
- [x] Audit duplicate docs
- [x] Audit duplicate CLIs
- [x] Audit executable scripts
- [x] Audit service files
- [x] Audit nginx/fpm config

## Phase 2 - Backend Target Structure

Target:

```text
backend-engine/
  bin/
    nexph
  core/
    Runtime/
    Server/
    Http/
    Package/
    Loader/
    Support/
  docs/
    runtime/
    cli/
    bags/
  examples/
    http/
    websocket/
    sse/
    bags/
  tests/
    Runtime/
    Server/
    Loader/
    Package/
  scripts/
    benchmarks/
    smoke/
  nexph.json
  README.md
  LICENSE
```

Tasks:

- [x] Confirm `core/Runtime` stays
- [x] Confirm `core/Server` stays
- [x] Confirm `core/Http` stays for stateless runtime
- [x] Confirm `core/Queue` stays if runtime-native queue
- [x] Confirm `core/Database` stays if core package
- [x] Confirm `core/Auth` becomes package candidate
- [x] Confirm `core/UI` becomes package or moves out
- [x] Confirm `app/` is demo/app-level
- [x] Confirm `routes/` is demo/app-level except examples
- [x] Confirm `public/` is stateless example or runtime public bridge
- [x] Confirm `metadata/` is example data
- [x] Confirm `storage/` runtime files are ignored
- [x] Confirm `serve.php` becomes internal bootstrap or CLI target
- [x] Confirm `serve` wrapper status
- [x] Confirm `stop` wrapper status
- [x] Confirm config files to keep
- [x] Confirm config files to move docs/examples

## Phase 3 - Backend Keep/Move/Delete Map

### Keep As Runtime Core

- [x] `autoload.php`
- [x] `bin/nexph`
- [x] `core/Runtime/`
- [x] `core/Server/`
- [x] `core/Http/`
- [x] `core/Support/`
- [x] `core/Queue/`
- [x] `core/Database/`
- [x] `core/Log/`
- [x] `serve.php`
- [x] `scripts/k6-users.js`
- [x] `scripts/k6-ws.js`
- [x] `scripts/k6-sse.js`
- [x] `scripts/server-test.php`
- [x] `scripts/ws-test.php`

### Candidate Package

- [x] `core/Auth/`
- [x] `core/Cache/`
- [x] `core/UI/`
- [x] `core/Generator/`
- [x] `routes/auth.php`
- [x] `routes/admin.php`
- [x] `public/api-explorer.html`
- [x] `public/observability.php`

### Move To Examples

- [x] `app/`
- [x] `routes/api.php`
- [x] `routes/debug.php`
- [x] `routes/health.php`
- [x] `metadata/`
- [x] `examples/`
- [x] `dashboard.html`
- [x] `keygen.php`

### Move To Deploy Docs

- [x] `nexph-nginx.conf`
- [x] `nexph-fpm-pool.conf`
- [x] `nexph-opcache.ini`
- [x] `nexph-worker.service`
- [x] `scripts/nexph-worker.service`
- [x] `scripts/nexph-worker.supervisor.conf`

### Remove Later After Backup

- [x] `public/index.backup.php`
- [x] `public/index.php.backup`
- [x] `public/index.optimized.php`
- [x] `storage/*.sqlite-shm`
- [x] `storage/*.sqlite-wal`
- [x] `tests/benchmark_results/`
- [x] `benchmark_results/`
- [ ] duplicate old docs

## Phase 4 - Frontend Target Structure

Target:

```text
frontend-engine/
  bin/
    nexph-ui
  src/
    Runtime/
    Compiler/
    Builder/
    DevServer/
    Plugin/
    Autoload/
  docs/
  examples/
  tests/
  dist/
  nexph.json
  README.md
  LICENSE
```

Tasks:

- [x] Confirm `src/Runtime` stays
- [x] Confirm `src/Compiler` stays
- [x] Confirm `src/Builder` stays
- [x] Confirm `src/DevServer` stays
- [x] Confirm `src/Plugin` stays
- [x] Confirm `src/Autoload` stays
- [x] Confirm `examples/` stays but minimal
- [x] Confirm `dist/` publish policy
- [x] Confirm `docs/` vs `docs-site/`
- [x] Confirm generated docs policy
- [x] Confirm frontend package manifest

## Phase 5 - Frontend Keep/Move/Delete Map

### Keep As Runtime Core

- [x] `autoload.php`
- [x] `bin/nexph`
- [x] `bags/runtime/nexph-core/src/`
- [x] `tests/`
- [x] `examples/`
- [x] `README.md`
- [x] `LICENSE`
- [x] `nexph.d.ts`
- [x] `phpunit.xml`

### Review Publish Policy

- [x] `dist/`
- [x] `docs-site/`
- [x] `.phpunit.result.cache`

### Move Or Merge

- [x] `docs/`
- [x] `docs-site/`
- [x] generated docs assets

## Phase 6 - CLI Distribution Design

- [x] Decide single CLI name `nexph`
- [x] Decide backend CLI ownership
- [x] Decide frontend CLI integration
- [x] Decide `nexph run`
- [x] Decide `nexph serve`
- [x] Decide `nexph dev`
- [x] Decide `nexph build`
- [x] Decide `nexph install`
- [x] Decide `nexph update`
- [x] Decide `nexph remove`
- [x] Decide `nexph module:list`
- [x] Decide `nexph runtime:stats`
- [x] Decide `nexph benchmark`
- [x] Decide CLI config lookup
- [x] Decide project root lookup
- [x] Decide bin install path
- [ ] Decide shell completion later

## Phase 7 - `nexph run` Design

- [x] Define `nexph run file.php`
- [x] Define argv forwarding
- [x] Define working directory behavior
- [x] Define bootstrap behavior
- [x] Define autoload behavior
- [x] Define package loader behavior
- [ ] Define Composer bridge behavior
- [x] Define env file loading
- [x] Define error formatting
- [x] Define exit code behavior
- [ ] Define stdin behavior
- [ ] Define signal behavior
- [x] Test run simple PHP file
- [x] Test run file with Nexph autoload
- [ ] Test run file with package import
- [ ] Test run file with Composer library

## Phase 8 - Package Manager Folder Plan

Target:

```text
backend-engine/core/Runtime/Package/
  PackageManager.php
  PackageManifest.php
  PackageLock.php
  PackageResolver.php
  PackageInstaller.php
  PackageDownloader.php
  PackageRepository.php
  PackageRegistryClient.php
  PackageVerifier.php
  PackageRemover.php
  PackageUpdater.php
```

Tasks:

- [x] Create package namespace plan
- [x] Create installer boundary
- [x] Create resolver boundary
- [x] Create downloader boundary
- [x] Create lockfile boundary
- [x] Create registry boundary
- [x] Create Composer bridge boundary
- [x] Create tests boundary

## Phase 9 - Runtime Loader Folder Plan

Target:

```text
backend-engine/core/Runtime/Loader/
  RuntimeLoader.php
  ModuleRegistry.php
  ModuleManifest.php
  ManifestParser.php
  ManifestValidator.php
  RuntimePreloader.php
  LazyModuleResolver.php
  Contracts/
  Exceptions/
```

Tasks:

- [x] Create loader namespace plan
- [x] Create manifest parser plan
- [x] Create registry plan
- [x] Create preloader plan
- [x] Create lazy resolver plan
- [x] Create contract plan
- [x] Create exceptions plan
- [x] Create tests plan

## Phase 10 - Manifest Files

- [ ] Add root workspace `nexph.json` later
- [x] Add backend `nexph.json`
- [x] Add frontend `nexph.json`
- [x] Add example package `nexph.json`
- [x] Add package fixture `nexph.json`
- [ ] Add lockfile spec docs
- [ ] Do not generate real `nexph.lock` until resolver exists

## Phase 11 - Documentation Cleanup

- [x] Backend README becomes runtime README
- [x] Frontend README becomes runtime/compiler README
- [x] Root README describes workspace
- [ ] Runtime docs split from old stateful doc
- [ ] Loader docs linked
- [ ] Package manager docs linked
- [ ] `nexph run` docs added
- [ ] CLI docs added
- [ ] Publish docs added
- [ ] Examples docs added

## Phase 12 - Test And Benchmark Cleanup

- [x] Move k6 scripts to `scripts/benchmarks/`
- [x] Move smoke scripts to `scripts/smoke/`
- [x] Keep HTTP k6 script
- [x] Keep WS k6 script
- [x] Keep SSE k6 script
- [x] Keep PHP server test
- [x] Document test commands
- [x] Remove old duplicate benchmark scripts later
- [x] Keep test fixtures minimal

## Phase 13 - Publish Hygiene

- [x] Ensure no `.env` committed in publish repo
- [x] Ensure no sqlite WAL committed
- [x] Ensure no generated cache committed
- [x] Ensure docs build artifacts policy clear
- [x] Ensure executable bits correct
- [x] Ensure LICENSE present
- [x] Ensure README clear
- [ ] Ensure version clear
- [x] Ensure package manifest clear
- [x] Ensure install instructions clear

## Phase 14 - Execution Order

- [x] Audit backend tree
- [x] Audit frontend tree
- [x] Write keep/move/delete report
- [x] Update `.gitignore`
- [x] Create target folders without moving code
- [x] Add manifests
- [x] Add CLI plan docs
- [x] Add loader/package folders skeleton
- [ ] Add tests skeleton
- [x] Move docs first
- [x] Move scripts second
- [x] Move examples third
- [ ] Refactor code only after docs/scripts stable
- [ ] Implement package manager
- [ ] Implement runtime loader
- [ ] Implement `nexph run`
- [ ] Implement Composer bridge
- [ ] Run smoke tests
- [ ] Update ROADMAP

