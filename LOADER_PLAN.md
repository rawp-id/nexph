# Nexph Package Manager & Runtime Loader Plan

Tujuan: membuat package manager native untuk Nexph, seperti Composer versi Nexph, tetapi tetap framework-first dan runtime-aware. Nexph harus bisa install package native tanpa Composer, tetap bisa install library Composer jika dibutuhkan, lalu Runtime Loader memuat package itu di stateless FPM maupun stateful runtime.

Visi utama:

- Nexph punya autoload dan package format sendiri
- Nexph tetap support library Composer lewat bridge
- Package bisa membawa route, middleware, command, config, runtime hook, dan provider
- Developer bebas bikin pattern seperti Express, MVC, functional routing, modular app, atau event-driven app
- Runtime Loader hanya load package yang sudah terinstall
- Package Manager yang mengurus install, remove, update, resolve dependency, dan lockfile

Contoh target command:

```bash
nexph install nexph/auth
nexph install nexph/cache
nexph install composer:monolog/monolog
nexph remove nexph/auth
nexph update
nexph publish
nexph module:list
```

## Phase 0 - Scope Lock

- [x] Bekukan fitur HTTP/WS/SSE selama loader dikerjakan
- [x] Loader tidak mengubah behavior runtime existing
- [x] Loader harus bisa off tanpa efek samping
- [x] Loader harus bisa jalan di stateless FPM
- [x] Loader harus bisa jalan di stateful CLI server
- [x] Composer Bridge harus opsional
- [x] Runtime package format harus sederhana dulu
- [x] Package manager harus jadi entry utama install package
- [x] Runtime loader hanya memuat package yang sudah resolved
- [x] Composer tidak boleh jadi dependency wajib
- [x] Composer package harus bisa dipasang lewat bridge
- [x] Native package harus jadi first-class package
- [x] Lockfile harus menjaga reproducible install
- [x] Framework pattern harus tetap bebas
- [x] Package sandbox research hanya desain awal
- [x] Semua error loader harus jelas
- [x] Semua perubahan harus punya test kecil

## Phase 0.5 - Product Shape

- [x] Definisikan Nexph Package Manager
- [x] Definisikan Nexph Runtime Loader
- [x] Definisikan hubungan package manager ke loader
- [x] Definisikan hubungan package manager ke Composer Bridge
- [x] Definisikan hubungan package manager ke CLI
- [x] Definisikan project root detection
- [x] Definisikan app manifest root `nexph.json`
- [x] Definisikan app lockfile `nexph.lock`
- [x] Definisikan native install dir `nexph_modules/`
- [x] Definisikan local dev package dir `packages/`
- [x] Definisikan Composer install dir `vendor/`
- [x] Definisikan cache dir `.nexph/cache/`
- [x] Definisikan registry cache dir `.nexph/registry/`
- [x] Definisikan package source priority
- [x] Definisikan command naming convention
- [x] Definisikan package namespace convention

## Phase 1 - Current Autoload Audit

- [x] Audit `backend-engine/autoload.php`
- [x] Audit `backend-engine/bin/nexph`
- [x] Audit `backend-engine/core/Runtime/CLI`
- [x] Audit `backend-engine/core/Support/Config.php`
- [x] Audit usage `require_once`
- [x] Audit usage `include`
- [x] Audit runtime bootstrap stateless
- [x] Audit runtime bootstrap stateful
- [x] Catat class path mapping saat ini
- [x] Catat file bootstrap wajib
- [x] Catat file yang bisa lazy-load
- [x] Catat file yang harus preload
- [x] Catat collision namespace potensial
- [x] Catat dependency ke Composer jika ada

## Phase 2 - Loader Boundary Design

- [x] Tentukan namespace loader
- [x] Tentukan folder `core/Runtime/Loader`
- [x] Tentukan public API loader
- [x] Tentukan internal class loader
- [x] Tentukan dependency loader ke `core/Support`
- [x] Tentukan dependency loader ke `core/Runtime`
- [x] Pastikan loader tidak tergantung `core/Server`
- [x] Pastikan loader tidak tergantung app routes
- [x] Pastikan loader tidak tergantung DB
- [x] Pastikan loader tidak tergantung session
- [x] Pastikan loader bisa dipakai CLI
- [x] Pastikan loader bisa dipakai FPM

## Phase 3 - Native Package Manifest Design

- [x] Pilih project manifest filename `nexph.json`
- [x] Pilih package manifest filename `nexph.json`
- [x] Bedakan project manifest dan package manifest
- [x] Definisikan field `name`
- [x] Definisikan field `version`
- [x] Definisikan field `type`
- [x] Definisikan field `description`
- [x] Definisikan field `autoload`
- [x] Definisikan field `preload`
- [x] Definisikan field `lazy`
- [x] Definisikan field `providers`
- [x] Definisikan field `routes`
- [x] Definisikan field `commands`
- [x] Definisikan field `hooks`
- [x] Definisikan field `config`
- [x] Definisikan field `requires`
- [x] Definisikan field `conflicts`
- [x] Definisikan field `sandbox`
- [x] Definisikan field `repositories`
- [x] Definisikan field `scripts`
- [x] Definisikan field `bin`
- [x] Definisikan field `license`
- [x] Definisikan field `authors`
- [x] Definisikan field `keywords`
- [x] Definisikan field `composer`
- [x] Definisikan minimal valid manifest
- [x] Definisikan full manifest example
- [x] Definisikan native library package example
- [x] Definisikan Express-like app package example
- [x] Definisikan route-only package example
- [x] Definisikan runtime-hook package example
- [x] Definisikan Composer-backed package example
- [x] Definisikan validation error format
- [x] Definisikan unknown field policy

## Phase 3.5 - Lockfile Design

- [x] Pilih lockfile filename `nexph.lock`
- [x] Definisikan lockfile version
- [x] Definisikan package name record
- [x] Definisikan package version record
- [x] Definisikan package source record
- [x] Definisikan package dist URL record
- [x] Definisikan package checksum record
- [x] Definisikan dependency graph record
- [x] Definisikan resolved Composer packages record
- [x] Definisikan installed path record
- [x] Definisikan generated timestamp
- [x] Definisikan PHP version constraint record
- [x] Definisikan platform extensions record
- [x] Definisikan lockfile write strategy
- [x] Definisikan lockfile read strategy
- [x] Definisikan lockfile conflict behavior
- [ ] Test lockfile parse
- [ ] Test lockfile write
- [ ] Test lockfile missing package

## Phase 4 - Loader Contracts

- [x] Buat kontrak `ModuleInterface`
- [x] Buat kontrak `ServiceProviderInterface`
- [x] Buat kontrak `RouteProviderInterface`
- [x] Buat kontrak `CommandProviderInterface`
- [x] Buat kontrak `HookProviderInterface`
- [x] Buat kontrak `ConfigProviderInterface`
- [x] Buat kontrak `PreloadableInterface`
- [x] Buat kontrak `BootableInterface`
- [x] Buat kontrak `ShutdownableInterface`
- [x] Buat kontrak lifecycle order
- [x] Buat kontrak error handling
- [x] Buat kontrak idempotency

## Phase 5 - Core Loader Classes

- [x] Buat `ModuleManifest`
- [x] Buat `ManifestParser`
- [x] Buat `ManifestValidator`
- [x] Buat `ModuleRegistry`
- [x] Buat `RuntimeLoader`
- [x] Buat `RuntimePreloader`
- [x] Buat `LazyModuleResolver`
- [x] Buat `ModuleDependencyResolver`
- [x] Buat `ModuleLoadException`
- [x] Buat `ManifestValidationException`
- [x] Buat `ModuleNotFoundException`
- [x] Buat `ModuleConflictException`

## Phase 5.5 - Package Manager Classes

- [x] Buat folder `core/Runtime/Package`
- [x] Buat `PackageManager`
- [x] Buat `PackageManifest`
- [x] Buat `PackageLock`
- [x] Buat `PackageResolver`
- [x] Buat `PackageInstaller`
- [ ] Buat `PackageDownloader`
- [ ] Buat `PackageRepository`
- [ ] Buat `PackageRegistryClient`
- [ ] Buat `PackageExtractor`
- [ ] Buat `PackageVerifier`
- [x] Buat `PackageRemover`
- [ ] Buat `PackageUpdater`
- [ ] Buat `PackagePublisher`
- [ ] Buat `PackageInstallException`
- [ ] Buat `PackageResolveException`
- [ ] Buat `PackageDownloadException`
- [ ] Buat `PackageVerifyException`

## Phase 6 - Runtime Preload

- [x] Preload file list dari manifest
- [x] Preload class list dari manifest
- [x] Preload provider class
- [x] Preload command provider
- [x] Preload route provider
- [x] Deduplicate preload file
- [x] Detect missing preload file
- [x] Detect preload file outside package
- [x] Detect preload exception
- [x] Track preload duration
- [x] Track preload count
- [x] Expose preload stats
- [x] Test preload success
- [x] Test preload missing file
- [x] Test preload duplicate file
- [x] Test preload outside package blocked

## Phase 7 - Lazy Module Resolver

- [x] Build lazy map dari manifest
- [x] Resolve class to module
- [x] Resolve file to module
- [x] Resolve provider only when needed
- [x] Resolve route provider only when route layer asks
- [x] Resolve command provider only when CLI asks
- [x] Cache lazy map in memory
- [ ] Optional cache lazy map to file
- [x] Detect lazy target missing
- [x] Detect lazy collision
- [x] Track lazy hit count
- [x] Track lazy miss count
- [x] Expose lazy stats
- [x] Test lazy class resolve
- [x] Test lazy missing target
- [x] Test lazy collision

## Phase 8 - Module Registry

- [x] Register module from path
- [x] Register module from manifest
- [x] Register multiple module paths
- [x] List registered modules
- [x] Get module by name
- [x] Check module enabled
- [x] Disable module
- [x] Enable module
- [x] Sort modules by dependency
- [x] Detect duplicate module name
- [x] Detect missing dependency
- [x] Detect version mismatch
- [x] Detect conflict
- [ ] Export registry snapshot
- [ ] Import registry snapshot
- [x] Expose registry stats
- [x] Test register one module
- [x] Test register multiple modules
- [x] Test duplicate module
- [x] Test dependency sort
- [x] Test conflict

## Phase 9 - Runtime Package Discovery

- [x] Define app modules path `modules/`
- [x] Define vendor modules path `vendor/*/*`
- [x] Define local packages path `packages/`
- [x] Discover `nexph.json` in app modules
- [x] Discover `nexph.json` in packages
- [x] Discover `nexph.json` in vendor packages
- [x] Ignore disabled package
- [x] Ignore invalid package with report
- [x] Support explicit module path config
- [x] Support no module path gracefully
- [x] Test empty discovery
- [x] Test modules path discovery
- [x] Test packages path discovery
- [x] Test invalid package discovery

## Phase 9.5 - Package Registry Protocol

- [ ] Definisikan registry base URL
- [ ] Definisikan package lookup endpoint
- [ ] Definisikan version metadata endpoint
- [ ] Definisikan dist download URL field
- [ ] Definisikan checksum field
- [ ] Definisikan signature field future
- [ ] Definisikan package search endpoint
- [ ] Definisikan publish endpoint future
- [ ] Definisikan auth token future
- [ ] Definisikan offline cache behavior
- [ ] Definisikan local registry override
- [ ] Definisikan Git source fallback
- [ ] Definisikan path source fallback
- [ ] Test registry response parse
- [ ] Test missing registry package
- [ ] Test cache fallback

## Phase 9.6 - Dependency Resolver

- [ ] Parse semver constraints
- [ ] Resolve exact version
- [ ] Resolve caret constraint
- [ ] Resolve tilde constraint
- [ ] Resolve wildcard constraint
- [ ] Resolve latest stable
- [ ] Resolve pre-release opt-in
- [ ] Resolve transitive dependency
- [ ] Detect circular dependency
- [ ] Detect version conflict
- [ ] Detect platform PHP mismatch
- [ ] Detect missing PHP extension
- [ ] Prefer locked version on install
- [ ] Update locked version on update
- [ ] Test exact resolve
- [ ] Test transitive resolve
- [ ] Test conflict resolve
- [ ] Test circular dependency

## Phase 10 - Composer Bridge Design

- [x] Define Composer bridge disabled mode
- [x] Define Composer bridge auto mode
- [x] Define Composer bridge required mode
- [x] Define `vendor/autoload.php` detection
- [x] Define `composer.json` detection
- [x] Define PSR-4 mapping behavior
- [x] Define `autoload.files` behavior
- [x] Define package metadata mapping
- [x] Define Composer package as module rule
- [x] Define native manifest priority
- [x] Define Composer fallback priority
- [x] Define bridge diagnostics
- [x] Define `composer:` package prefix
- [x] Define Composer install command strategy
- [x] Define Composer package lock sync
- [x] Define Composer library as non-runtime package by default

## Phase 11 - Composer Bridge Implementation

- [x] Buat `ComposerBridge`
- [x] Buat `ComposerInstaller`
- [x] Detect project composer root
- [x] Detect vendor autoload
- [x] Detect Composer binary
- [x] Run Composer install for `composer:` package
- [x] Run Composer require for `composer:` package
- [x] Require vendor autoload once
- [x] Parse root `composer.json`
- [x] Parse installed package metadata if available
- [x] Parse PSR-4 autoload
- [x] Parse files autoload
- [x] Map package name
- [x] Map package version
- [x] Convert Composer package to module metadata
- [x] Respect native `nexph.json` override
- [x] Expose bridge stats
- [x] Expose bridge diagnostics
- [ ] Test no composer
- [ ] Test composer root exists
- [ ] Test vendor autoload exists
- [ ] Test PSR-4 map
- [ ] Test files autoload
- [ ] Test composer binary missing
- [ ] Test composer package install dry-run

## Phase 12 - Hybrid Package Loading

- [ ] Load native package first
- [ ] Load Composer bridge second
- [ ] Allow native package to wrap Composer package
- [ ] Allow Composer fallback for classes
- [ ] Prevent duplicate provider registration
- [ ] Prevent duplicate route registration
- [ ] Prevent duplicate command registration
- [ ] Record source `native`
- [ ] Record source `composer`
- [ ] Record source `hybrid`
- [ ] Test native only
- [ ] Test composer only
- [ ] Test hybrid package
- [ ] Test duplicate provider prevention

## Phase 12.5 - Install/Remove/Update Flow

- [ ] Implement `nexph install <package>`
- [ ] Implement `nexph install composer:<vendor/package>`
- [ ] Implement `nexph remove <package>`
- [ ] Implement `nexph update`
- [ ] Implement `nexph update <package>`
- [ ] Implement `nexph restore` from lockfile
- [ ] Implement dry-run mode
- [ ] Implement no-scripts mode
- [ ] Implement offline mode
- [ ] Install native package into `nexph_modules/`
- [ ] Install path package by symlink or copy
- [ ] Install Composer package into `vendor/`
- [ ] Update `nexph.json`
- [ ] Update `nexph.lock`
- [ ] Rebuild module registry after install
- [ ] Rebuild autoload map after install
- [ ] Test install native package
- [ ] Test install Composer package
- [ ] Test remove native package
- [ ] Test update package
- [ ] Test restore lockfile

## Phase 13 - Runtime Integration Points

- [ ] Integrate loader into stateless bootstrap
- [ ] Integrate loader into stateful `serve.php`
- [ ] Integrate provider boot before routes
- [ ] Integrate route providers with router
- [ ] Integrate command providers with CLI
- [ ] Integrate config providers before runtime start
- [ ] Integrate hooks into runtime lifecycle
- [ ] Ensure loader off by default if no modules
- [ ] Ensure loader errors do not hide fatal cause
- [ ] Ensure stateful workers do not double-boot unsafe modules
- [ ] Test stateless bootstrap with no modules
- [ ] Test stateful server with no modules
- [ ] Test module route in stateless mode
- [ ] Test module route in stateful mode

## Phase 13.5 - Framework Pattern Support

- [ ] Support Express-like route module
- [ ] Support MVC module
- [ ] Support functional routing module
- [ ] Support middleware package
- [ ] Support event-driven package
- [ ] Support CLI-only package
- [ ] Support config-only package
- [ ] Support runtime-hook package
- [ ] Ensure packages can be pattern-agnostic
- [ ] Document pattern examples
- [ ] Test Express-like package fixture
- [ ] Test middleware package fixture
- [ ] Test CLI-only package fixture

## Phase 14 - Security & Sandbox Research

- [ ] Define package trust levels
- [ ] Define sandbox metadata fields
- [ ] Define disallowed file traversal
- [ ] Block preload outside package root
- [ ] Block lazy file outside package root
- [ ] Validate provider class namespace
- [ ] Validate route file path
- [ ] Validate command file path
- [ ] Document PHP sandbox limits
- [ ] Document trusted package model
- [ ] Document future sandbox research
- [ ] Test path traversal block
- [ ] Test invalid provider block

## Phase 15 - Observability

- [ ] Add loader stats
- [ ] Add module count metric
- [ ] Add module load errors metric
- [ ] Add preload count metric
- [ ] Add preload duration metric
- [ ] Add lazy hits metric
- [ ] Add lazy misses metric
- [ ] Add Composer bridge status metric
- [ ] Add registry health info
- [ ] Add loader diagnostics route or CLI output
- [ ] Document loader metrics

## Phase 16 - CLI Commands

- [x] Add `nexph install`
- [x] Add `nexph remove`
- [ ] Add `nexph update`
- [ ] Add `nexph restore`
- [ ] Add `nexph publish`
- [ ] Add `nexph search`
- [ ] Add `nexph package:info`
- [x] Add `nexph module:list`
- [ ] Add `nexph module:info`
- [x] Add `nexph module:validate`
- [ ] Add `nexph module:discover`
- [ ] Add `nexph module:preload`
- [ ] Add `nexph package:init`
- [ ] Add `nexph package:validate`
- [ ] Add `nexph composer:bridge`
- [x] Add JSON output option
- [ ] Add quiet output option
- [x] Test module list
- [x] Test module validate
- [ ] Test package init
- [x] Test package install
- [x] Test package remove
- [ ] Test package update

## Phase 17 - Test Fixtures

- [ ] Create valid minimal package fixture
- [ ] Create valid full package fixture
- [ ] Create route package fixture
- [ ] Create command package fixture
- [ ] Create config package fixture
- [ ] Create invalid JSON fixture
- [ ] Create missing name fixture
- [ ] Create missing version fixture
- [ ] Create dependency missing fixture
- [ ] Create conflict fixture
- [ ] Create path traversal fixture
- [ ] Create Composer fixture
- [ ] Create hybrid fixture
- [ ] Create Express-like package fixture
- [ ] Create middleware package fixture
- [ ] Create CLI-only package fixture
- [ ] Create package registry fixture
- [ ] Create lockfile fixture

## Phase 18 - Documentation

- [ ] Write `docs/runtime/package-manager.md`
- [ ] Write `docs/runtime/loader.md`
- [ ] Write `docs/runtime/packages.md`
- [ ] Write `docs/runtime/composer-bridge.md`
- [ ] Write manifest field reference
- [ ] Write lifecycle order docs
- [ ] Write provider docs
- [ ] Write preload docs
- [ ] Write lazy resolver docs
- [ ] Write module registry docs
- [ ] Write security notes
- [ ] Write package examples
- [ ] Write install command docs
- [ ] Write remove command docs
- [ ] Write update command docs
- [ ] Write lockfile docs
- [ ] Write Composer install docs
- [ ] Write Express-like package docs
- [ ] Write CLI examples
- [ ] Link docs from README

## Phase 19 - Publish Readiness

- [ ] Mark ROADMAP loader items done when implemented
- [ ] Run PHP lint
- [ ] Run loader unit tests
- [ ] Run stateless smoke test
- [ ] Run stateful HTTP smoke test
- [ ] Run module fixture smoke test
- [ ] Run package install fixture smoke test
- [ ] Run lockfile restore smoke test
- [ ] Run Composer bridge smoke test
- [ ] Review public API names
- [ ] Review namespace stability
- [ ] Review manifest stability
- [ ] Review docs completeness
- [ ] Draft changelog loader section
- [ ] Draft release note loader section
