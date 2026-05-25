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

- [ ] Bekukan fitur HTTP/WS/SSE selama loader dikerjakan
- [ ] Loader tidak mengubah behavior runtime existing
- [ ] Loader harus bisa off tanpa efek samping
- [ ] Loader harus bisa jalan di stateless FPM
- [ ] Loader harus bisa jalan di stateful CLI server
- [ ] Composer Bridge harus opsional
- [ ] Runtime package format harus sederhana dulu
- [ ] Package manager harus jadi entry utama install package
- [ ] Runtime loader hanya memuat package yang sudah resolved
- [ ] Composer tidak boleh jadi dependency wajib
- [ ] Composer package harus bisa dipasang lewat bridge
- [ ] Native package harus jadi first-class package
- [ ] Lockfile harus menjaga reproducible install
- [ ] Framework pattern harus tetap bebas
- [ ] Package sandbox research hanya desain awal
- [ ] Semua error loader harus jelas
- [ ] Semua perubahan harus punya test kecil

## Phase 0.5 - Product Shape

- [ ] Definisikan Nexph Package Manager
- [ ] Definisikan Nexph Runtime Loader
- [ ] Definisikan hubungan package manager ke loader
- [ ] Definisikan hubungan package manager ke Composer Bridge
- [ ] Definisikan hubungan package manager ke CLI
- [ ] Definisikan project root detection
- [ ] Definisikan app manifest root `nexph.json`
- [ ] Definisikan app lockfile `nexph.lock`
- [ ] Definisikan native install dir `nexph_modules/`
- [ ] Definisikan local dev package dir `packages/`
- [ ] Definisikan Composer install dir `vendor/`
- [ ] Definisikan cache dir `.nexph/cache/`
- [ ] Definisikan registry cache dir `.nexph/registry/`
- [ ] Definisikan package source priority
- [ ] Definisikan command naming convention
- [ ] Definisikan package namespace convention

## Phase 1 - Current Autoload Audit

- [ ] Audit `backend-engine/autoload.php`
- [ ] Audit `backend-engine/bin/nexph`
- [ ] Audit `backend-engine/core/Runtime/CLI`
- [ ] Audit `backend-engine/core/Support/Config.php`
- [ ] Audit usage `require_once`
- [ ] Audit usage `include`
- [ ] Audit runtime bootstrap stateless
- [ ] Audit runtime bootstrap stateful
- [ ] Catat class path mapping saat ini
- [ ] Catat file bootstrap wajib
- [ ] Catat file yang bisa lazy-load
- [ ] Catat file yang harus preload
- [ ] Catat collision namespace potensial
- [ ] Catat dependency ke Composer jika ada

## Phase 2 - Loader Boundary Design

- [ ] Tentukan namespace loader
- [ ] Tentukan folder `core/Runtime/Loader`
- [ ] Tentukan public API loader
- [ ] Tentukan internal class loader
- [ ] Tentukan dependency loader ke `core/Support`
- [ ] Tentukan dependency loader ke `core/Runtime`
- [ ] Pastikan loader tidak tergantung `core/Server`
- [ ] Pastikan loader tidak tergantung app routes
- [ ] Pastikan loader tidak tergantung DB
- [ ] Pastikan loader tidak tergantung session
- [ ] Pastikan loader bisa dipakai CLI
- [ ] Pastikan loader bisa dipakai FPM

## Phase 3 - Native Package Manifest Design

- [ ] Pilih project manifest filename `nexph.json`
- [ ] Pilih package manifest filename `nexph.json`
- [ ] Bedakan project manifest dan package manifest
- [ ] Definisikan field `name`
- [ ] Definisikan field `version`
- [ ] Definisikan field `type`
- [ ] Definisikan field `description`
- [ ] Definisikan field `autoload`
- [ ] Definisikan field `preload`
- [ ] Definisikan field `lazy`
- [ ] Definisikan field `providers`
- [ ] Definisikan field `routes`
- [ ] Definisikan field `commands`
- [ ] Definisikan field `hooks`
- [ ] Definisikan field `config`
- [ ] Definisikan field `requires`
- [ ] Definisikan field `conflicts`
- [ ] Definisikan field `sandbox`
- [ ] Definisikan field `repositories`
- [ ] Definisikan field `scripts`
- [ ] Definisikan field `bin`
- [ ] Definisikan field `license`
- [ ] Definisikan field `authors`
- [ ] Definisikan field `keywords`
- [ ] Definisikan field `composer`
- [ ] Definisikan minimal valid manifest
- [ ] Definisikan full manifest example
- [ ] Definisikan native library package example
- [ ] Definisikan Express-like app package example
- [ ] Definisikan route-only package example
- [ ] Definisikan runtime-hook package example
- [ ] Definisikan Composer-backed package example
- [ ] Definisikan validation error format
- [ ] Definisikan unknown field policy

## Phase 3.5 - Lockfile Design

- [ ] Pilih lockfile filename `nexph.lock`
- [ ] Definisikan lockfile version
- [ ] Definisikan package name record
- [ ] Definisikan package version record
- [ ] Definisikan package source record
- [ ] Definisikan package dist URL record
- [ ] Definisikan package checksum record
- [ ] Definisikan dependency graph record
- [ ] Definisikan resolved Composer packages record
- [ ] Definisikan installed path record
- [ ] Definisikan generated timestamp
- [ ] Definisikan PHP version constraint record
- [ ] Definisikan platform extensions record
- [ ] Definisikan lockfile write strategy
- [ ] Definisikan lockfile read strategy
- [ ] Definisikan lockfile conflict behavior
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
- [ ] Buat `ModuleDependencyResolver`
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

- [ ] Preload file list dari manifest
- [ ] Preload class list dari manifest
- [ ] Preload provider class
- [ ] Preload command provider
- [ ] Preload route provider
- [ ] Deduplicate preload file
- [ ] Detect missing preload file
- [ ] Detect preload file outside package
- [ ] Detect preload exception
- [ ] Track preload duration
- [ ] Track preload count
- [ ] Expose preload stats
- [ ] Test preload success
- [ ] Test preload missing file
- [ ] Test preload duplicate file
- [ ] Test preload outside package blocked

## Phase 7 - Lazy Module Resolver

- [ ] Build lazy map dari manifest
- [ ] Resolve class to module
- [ ] Resolve file to module
- [ ] Resolve provider only when needed
- [ ] Resolve route provider only when route layer asks
- [ ] Resolve command provider only when CLI asks
- [ ] Cache lazy map in memory
- [ ] Optional cache lazy map to file
- [ ] Detect lazy target missing
- [ ] Detect lazy collision
- [ ] Track lazy hit count
- [ ] Track lazy miss count
- [ ] Expose lazy stats
- [ ] Test lazy class resolve
- [ ] Test lazy missing target
- [ ] Test lazy collision

## Phase 8 - Module Registry

- [ ] Register module from path
- [ ] Register module from manifest
- [ ] Register multiple module paths
- [ ] List registered modules
- [ ] Get module by name
- [ ] Check module enabled
- [ ] Disable module
- [ ] Enable module
- [ ] Sort modules by dependency
- [ ] Detect duplicate module name
- [ ] Detect missing dependency
- [ ] Detect version mismatch
- [ ] Detect conflict
- [ ] Export registry snapshot
- [ ] Import registry snapshot
- [ ] Expose registry stats
- [ ] Test register one module
- [ ] Test register multiple modules
- [ ] Test duplicate module
- [ ] Test dependency sort
- [ ] Test conflict

## Phase 9 - Runtime Package Discovery

- [ ] Define app modules path `modules/`
- [ ] Define vendor modules path `vendor/*/*`
- [ ] Define local packages path `packages/`
- [ ] Discover `nexph.json` in app modules
- [ ] Discover `nexph.json` in packages
- [ ] Discover `nexph.json` in vendor packages
- [ ] Ignore disabled package
- [ ] Ignore invalid package with report
- [ ] Support explicit module path config
- [ ] Support no module path gracefully
- [ ] Test empty discovery
- [ ] Test modules path discovery
- [ ] Test packages path discovery
- [ ] Test invalid package discovery

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

- [ ] Define Composer bridge disabled mode
- [ ] Define Composer bridge auto mode
- [ ] Define Composer bridge required mode
- [ ] Define `vendor/autoload.php` detection
- [ ] Define `composer.json` detection
- [ ] Define PSR-4 mapping behavior
- [ ] Define `autoload.files` behavior
- [ ] Define package metadata mapping
- [ ] Define Composer package as module rule
- [ ] Define native manifest priority
- [ ] Define Composer fallback priority
- [ ] Define bridge diagnostics
- [ ] Define `composer:` package prefix
- [ ] Define Composer install command strategy
- [ ] Define Composer package lock sync
- [ ] Define Composer library as non-runtime package by default

## Phase 11 - Composer Bridge Implementation

- [ ] Buat `ComposerBridge`
- [ ] Buat `ComposerInstaller`
- [ ] Detect project composer root
- [ ] Detect vendor autoload
- [ ] Detect Composer binary
- [ ] Run Composer install for `composer:` package
- [ ] Run Composer require for `composer:` package
- [ ] Require vendor autoload once
- [ ] Parse root `composer.json`
- [ ] Parse installed package metadata if available
- [ ] Parse PSR-4 autoload
- [ ] Parse files autoload
- [ ] Map package name
- [ ] Map package version
- [ ] Convert Composer package to module metadata
- [ ] Respect native `nexph.json` override
- [ ] Expose bridge stats
- [ ] Expose bridge diagnostics
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

- [ ] Add `nexph install`
- [ ] Add `nexph remove`
- [ ] Add `nexph update`
- [ ] Add `nexph restore`
- [ ] Add `nexph publish`
- [ ] Add `nexph search`
- [ ] Add `nexph package:info`
- [ ] Add `nexph module:list`
- [ ] Add `nexph module:info`
- [ ] Add `nexph module:validate`
- [ ] Add `nexph module:discover`
- [ ] Add `nexph module:preload`
- [ ] Add `nexph package:init`
- [ ] Add `nexph package:validate`
- [ ] Add `nexph composer:bridge`
- [ ] Add JSON output option
- [ ] Add quiet output option
- [ ] Test module list
- [ ] Test module validate
- [ ] Test package init
- [ ] Test package install
- [ ] Test package remove
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
