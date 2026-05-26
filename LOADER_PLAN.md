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
- [x] Definisikan native install dir `bags/installed/`
- [x] Definisikan local dev package dir `bags/local/`
- [x] Definisikan Composer install dir `vendor/`
- [x] Definisikan cache dir `bags/cache/`
- [x] Definisikan registry cache dir `bags/cache/registry/`
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
- [x] Buat `PackageDownloader`
- [x] Buat `PackageRepository`
- [x] Buat `PackageRegistryClient`
- [x] Buat `PackageExtractor`
- [x] Buat `PackageVerifier`
- [x] Buat `PackageRemover`
- [x] Buat `PackageUpdater`
- [x] Buat `PackagePublisher`
- [x] Buat `PackageInstallException`
- [x] Buat `PackageResolveException`
- [x] Buat `PackageDownloadException`
- [x] Buat `PackageVerifyException`

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
- [x] Define local packages path `bags/local/`
- [x] Discover `nexph.json` in app modules
- [x] Discover `nexph.json` in `bags/local`
- [x] Discover `nexph.json` in vendor packages
- [x] Ignore disabled package
- [x] Ignore invalid package with report
- [x] Support explicit module path config
- [x] Support no module path gracefully
- [x] Test empty discovery
- [x] Test modules path discovery
- [x] Test `bags/local` path discovery
- [x] Test invalid package discovery

## Phase 9.5 - Package Registry Protocol

- [x] Definisikan registry base URL
- [x] Definisikan package lookup endpoint
- [x] Definisikan version metadata endpoint
- [x] Definisikan dist download URL field
- [x] Definisikan checksum field
- [x] Definisikan signature field future
- [x] Definisikan package search endpoint
- [x] Definisikan publish endpoint future
- [x] Definisikan auth token future
- [x] Definisikan offline cache behavior
- [x] Definisikan local registry override
- [x] Definisikan Git source fallback
- [x] Definisikan path source fallback
- [x] Test registry response parse
- [x] Test missing registry package
- [x] Test cache fallback

## Phase 9.6 - Dependency Resolver

- [x] Parse semver constraints
- [x] Resolve exact version
- [x] Resolve caret constraint
- [x] Resolve tilde constraint
- [x] Resolve wildcard constraint
- [x] Resolve latest stable
- [x] Resolve pre-release opt-in
- [x] Resolve transitive dependency
- [x] Detect circular dependency
- [x] Detect version conflict
- [x] Detect platform PHP mismatch
- [x] Detect missing PHP extension
- [x] Prefer locked version on install
- [x] Update locked version on update
- [x] Test exact resolve
- [x] Test transitive resolve
- [x] Test conflict resolve
- [x] Test circular dependency

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

- [x] Load native package first
- [x] Load Composer bridge second
- [x] Allow native package to wrap Composer package
- [x] Allow Composer fallback for classes
- [x] Prevent duplicate provider registration
- [x] Prevent duplicate route registration
- [x] Prevent duplicate command registration
- [x] Record source `native`
- [x] Record source `composer`
- [x] Record source `hybrid`
- [x] Test native only
- [x] Test composer only
- [x] Test hybrid package
- [x] Test duplicate provider prevention

## Phase 12.5 - Install/Remove/Update Flow

- [x] Implement `nexph install <package>`
- [x] Implement `nexph install composer:<vendor/package>`
- [x] Implement `nexph remove <package>`
- [x] Implement `nexph update`
- [x] Implement `nexph update <package>`
- [x] Implement `nexph restore` from lockfile
- [ ] Implement dry-run mode
- [ ] Implement no-scripts mode
- [ ] Implement offline mode
- [x] Install native package into `bags/installed/`
- [ ] Install path package by symlink or copy
- [x] Install Composer package into `vendor/`
- [x] Update `nexph.json`
- [x] Update `nexph.lock`
- [x] Rebuild module registry after install
- [x] Rebuild autoload map after install
- [x] Test install native package
- [x] Test install Composer package
- [x] Test remove native package
- [x] Test update package
- [x] Test restore lockfile

## Phase 13 - Runtime Integration Points

- [x] Integrate loader into stateless bootstrap
- [x] Integrate loader into stateful `serve.php`
- [x] Integrate provider boot before routes
- [x] Integrate route providers with router
- [x] Integrate command providers with CLI
- [x] Integrate config providers before runtime start
- [x] Integrate hooks into runtime lifecycle
- [x] Ensure loader off by default if no modules
- [x] Ensure loader errors do not hide fatal cause
- [x] Ensure stateful workers do not double-boot unsafe modules
- [x] Test stateless bootstrap with no modules
- [x] Test stateful server with no modules
- [ ] Test module route in stateless mode
- [ ] Test module route in stateful mode

## Phase 13.5 - Framework Pattern Support

- [x] Support Express-like route module
- [x] Support MVC module
- [x] Support functional routing module
- [x] Support middleware package
- [x] Support event-driven package
- [x] Support CLI-only package
- [x] Support config-only package
- [x] Support runtime-hook package
- [x] Ensure packages can be pattern-agnostic
- [x] Document pattern examples
- [x] Test Express-like package fixture
- [x] Test middleware package fixture
- [x] Test CLI-only package fixture

## Phase 14 - Security & Sandbox Research

- [x] Define package trust levels
- [x] Define sandbox metadata fields
- [x] Define disallowed file traversal
- [x] Block preload outside package root
- [x] Block lazy file outside package root
- [x] Validate provider class namespace
- [x] Validate route file path
- [x] Validate command file path
- [x] Document PHP sandbox limits
- [x] Document trusted package model
- [x] Document future sandbox research
- [x] Test path traversal block
- [x] Test invalid provider block

## Phase 15 - Observability

- [x] Add loader stats
- [x] Add module count metric
- [x] Add module load errors metric
- [x] Add preload count metric
- [x] Add preload duration metric
- [x] Add lazy hits metric
- [x] Add lazy misses metric
- [x] Add Composer bridge status metric
- [x] Add registry health info
- [x] Add loader diagnostics route or CLI output
- [x] Document loader metrics

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

- [x] Create valid minimal package fixture
- [x] Create valid full package fixture
- [x] Create route package fixture
- [x] Create command package fixture
- [x] Create config package fixture
- [x] Create invalid JSON fixture
- [x] Create missing name fixture
- [x] Create missing version fixture
- [x] Create dependency missing fixture
- [x] Create conflict fixture
- [x] Create path traversal fixture
- [ ] Create Composer fixture
- [ ] Create hybrid fixture
- [x] Create Express-like package fixture
- [x] Create middleware package fixture
- [x] Create CLI-only package fixture
- [ ] Create package registry fixture
- [ ] Create lockfile fixture

## Phase 18 - Documentation

- [x] Write `docs/runtime/package-manager.md`
- [x] Write `docs/runtime/loader.md`
- [ ] Write `docs/runtime/packages.md`
- [x] Write `docs/runtime/composer-bridge.md`
- [x] Write manifest field reference
- [x] Write lifecycle order docs
- [x] Write provider docs
- [x] Write preload docs
- [x] Write lazy resolver docs
- [x] Write module registry docs
- [x] Write security notes
- [x] Write package examples
- [x] Write install command docs
- [x] Write remove command docs
- [x] Write update command docs
- [x] Write lockfile docs
- [x] Write Composer install docs
- [x] Write Express-like package docs
- [x] Write CLI examples
- [x] Link docs from README

## Phase 19 - Publish Readiness

- [ ] Mark ROADMAP loader items done when implemented
- [x] Run PHP lint
- [x] Run loader unit tests
- [x] Run stateless smoke test
- [x] Run stateful HTTP smoke test
- [x] Run module fixture smoke test
- [x] Run package install fixture smoke test
- [ ] Run lockfile restore smoke test
- [ ] Run Composer bridge smoke test
- [x] Review public API names
- [x] Review namespace stability
- [x] Review manifest stability
- [x] Review docs completeness
- [ ] Draft changelog loader section
- [ ] Draft release note loader section
