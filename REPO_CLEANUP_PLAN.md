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
    packages/
  examples/
    http/
    websocket/
    sse/
    packages/
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

- [ ] Confirm `core/Runtime` stays
- [ ] Confirm `core/Server` stays
- [ ] Confirm `core/Http` stays for stateless runtime
- [ ] Confirm `core/Queue` stays if runtime-native queue
- [ ] Confirm `core/Database` stays if core package
- [ ] Confirm `core/Auth` becomes package candidate
- [ ] Confirm `core/UI` becomes package or moves out
- [ ] Confirm `app/` is demo/app-level
- [ ] Confirm `routes/` is demo/app-level except examples
- [ ] Confirm `public/` is stateless example or runtime public bridge
- [ ] Confirm `metadata/` is example data
- [ ] Confirm `storage/` runtime files are ignored
- [ ] Confirm `serve.php` becomes internal bootstrap or CLI target
- [ ] Confirm `serve` wrapper status
- [ ] Confirm `stop` wrapper status
- [ ] Confirm config files to keep
- [ ] Confirm config files to move docs/examples

## Phase 3 - Backend Keep/Move/Delete Map

### Keep As Runtime Core

- [ ] `autoload.php`
- [ ] `bin/nexph`
- [ ] `core/Runtime/`
- [ ] `core/Server/`
- [ ] `core/Http/`
- [ ] `core/Support/`
- [ ] `core/Queue/`
- [ ] `core/Database/`
- [ ] `core/Log/`
- [ ] `serve.php`
- [ ] `scripts/k6-users.js`
- [ ] `scripts/k6-ws.js`
- [ ] `scripts/k6-sse.js`
- [ ] `scripts/server-test.php`
- [ ] `scripts/ws-test.php`

### Candidate Package

- [ ] `core/Auth/`
- [ ] `core/Cache/`
- [ ] `core/UI/`
- [ ] `core/Generator/`
- [ ] `routes/auth.php`
- [ ] `routes/admin.php`
- [ ] `public/api-explorer.html`
- [ ] `public/observability.php`

### Move To Examples

- [ ] `app/`
- [ ] `routes/api.php`
- [ ] `routes/debug.php`
- [ ] `routes/health.php`
- [ ] `metadata/`
- [ ] `examples/`
- [ ] `dashboard.html`
- [ ] `keygen.php`

### Move To Deploy Docs

- [ ] `nexph-nginx.conf`
- [ ] `nexph-fpm-pool.conf`
- [ ] `nexph-opcache.ini`
- [ ] `nexph-worker.service`
- [ ] `scripts/nexph-worker.service`
- [ ] `scripts/nexph-worker.supervisor.conf`

### Remove Later After Backup

- [ ] `public/index.backup.php`
- [ ] `public/index.php.backup`
- [ ] `public/index.optimized.php`
- [ ] `storage/*.sqlite-shm`
- [ ] `storage/*.sqlite-wal`
- [ ] `tests/benchmark_results/`
- [ ] `benchmark_results/`
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

- [ ] Confirm `src/Runtime` stays
- [ ] Confirm `src/Compiler` stays
- [ ] Confirm `src/Builder` stays
- [ ] Confirm `src/DevServer` stays
- [ ] Confirm `src/Plugin` stays
- [ ] Confirm `src/Autoload` stays
- [ ] Confirm `examples/` stays but minimal
- [ ] Confirm `dist/` publish policy
- [ ] Confirm `docs/` vs `docs-site/`
- [ ] Confirm generated docs policy
- [ ] Confirm frontend package manifest

## Phase 5 - Frontend Keep/Move/Delete Map

### Keep As Runtime Core

- [ ] `autoload.php`
- [ ] `bin/nexph`
- [ ] `src/`
- [ ] `tests/`
- [ ] `examples/`
- [ ] `README.md`
- [ ] `LICENSE`
- [ ] `nexph.d.ts`
- [ ] `phpunit.xml`

### Review Publish Policy

- [ ] `dist/`
- [ ] `docs-site/`
- [ ] `.phpunit.result.cache`

### Move Or Merge

- [ ] `docs/`
- [ ] `docs-site/`
- [ ] generated docs assets

## Phase 6 - CLI Distribution Design

- [ ] Decide single CLI name `nexph`
- [ ] Decide backend CLI ownership
- [ ] Decide frontend CLI integration
- [ ] Decide `nexph run`
- [ ] Decide `nexph serve`
- [ ] Decide `nexph dev`
- [ ] Decide `nexph build`
- [ ] Decide `nexph install`
- [ ] Decide `nexph update`
- [ ] Decide `nexph remove`
- [ ] Decide `nexph module:list`
- [ ] Decide `nexph runtime:stats`
- [ ] Decide `nexph benchmark`
- [ ] Decide CLI config lookup
- [ ] Decide project root lookup
- [ ] Decide bin install path
- [ ] Decide shell completion later

## Phase 7 - `nexph run` Design

- [ ] Define `nexph run file.php`
- [ ] Define argv forwarding
- [ ] Define working directory behavior
- [ ] Define bootstrap behavior
- [ ] Define autoload behavior
- [ ] Define package loader behavior
- [ ] Define Composer bridge behavior
- [ ] Define env file loading
- [ ] Define error formatting
- [ ] Define exit code behavior
- [ ] Define stdin behavior
- [ ] Define signal behavior
- [ ] Test run simple PHP file
- [ ] Test run file with Nexph autoload
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

- [ ] Create package namespace plan
- [ ] Create installer boundary
- [ ] Create resolver boundary
- [ ] Create downloader boundary
- [ ] Create lockfile boundary
- [ ] Create registry boundary
- [ ] Create Composer bridge boundary
- [ ] Create tests boundary

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

- [ ] Create loader namespace plan
- [ ] Create manifest parser plan
- [ ] Create registry plan
- [ ] Create preloader plan
- [ ] Create lazy resolver plan
- [ ] Create contract plan
- [ ] Create exceptions plan
- [ ] Create tests plan

## Phase 10 - Manifest Files

- [ ] Add root workspace `nexph.json` later
- [ ] Add backend `nexph.json`
- [ ] Add frontend `nexph.json`
- [ ] Add example package `nexph.json`
- [ ] Add package fixture `nexph.json`
- [ ] Add lockfile spec docs
- [ ] Do not generate real `nexph.lock` until resolver exists

## Phase 11 - Documentation Cleanup

- [ ] Backend README becomes runtime README
- [ ] Frontend README becomes runtime/compiler README
- [ ] Root README describes workspace
- [ ] Runtime docs split from old stateful doc
- [ ] Loader docs linked
- [ ] Package manager docs linked
- [ ] `nexph run` docs added
- [ ] CLI docs added
- [ ] Publish docs added
- [ ] Examples docs added

## Phase 12 - Test And Benchmark Cleanup

- [ ] Move k6 scripts to `scripts/benchmarks/`
- [ ] Move smoke scripts to `scripts/smoke/`
- [ ] Keep HTTP k6 script
- [ ] Keep WS k6 script
- [ ] Keep SSE k6 script
- [ ] Keep PHP server test
- [ ] Document test commands
- [ ] Remove old duplicate benchmark scripts later
- [ ] Keep test fixtures minimal

## Phase 13 - Publish Hygiene

- [ ] Ensure no `.env` committed in publish repo
- [ ] Ensure no sqlite WAL committed
- [ ] Ensure no generated cache committed
- [ ] Ensure docs build artifacts policy clear
- [ ] Ensure executable bits correct
- [ ] Ensure LICENSE present
- [ ] Ensure README clear
- [ ] Ensure version clear
- [ ] Ensure package manifest clear
- [ ] Ensure install instructions clear

## Phase 14 - Execution Order

- [ ] Audit backend tree
- [ ] Audit frontend tree
- [ ] Write keep/move/delete report
- [ ] Update `.gitignore`
- [ ] Create target folders without moving code
- [ ] Add manifests
- [ ] Add CLI plan docs
- [ ] Add loader/package folders skeleton
- [ ] Add tests skeleton
- [ ] Move docs first
- [ ] Move scripts second
- [ ] Move examples third
- [ ] Refactor code only after docs/scripts stable
- [ ] Implement package manager
- [ ] Implement runtime loader
- [ ] Implement `nexph run`
- [ ] Implement Composer bridge
- [ ] Run smoke tests
- [ ] Update ROADMAP

