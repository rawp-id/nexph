# Nexph Workspace Audit Report

Generated: 2026-05-25

## Root Workspace `/home/rawp/Tech/nexph/`

| File | Status | Note |
|------|--------|------|
| LOADER_PLAN.md | KEEP | Plan doc |
| REPO_CLEANUP_PLAN.md | KEEP | Plan doc |
| ROADMAP.md | KEEP | Plan doc |
| .git | KEEP | Root workspace git |

---

## Backend Engine Audit

### Root Files

| File | Status | Action |
|------|--------|--------|
| autoload.php | KEEP | Runtime core autoloader |
| serve.php | KEEP | Stateful server bootstrap |
| serve | REVIEW | Shell wrapper — keep if useful |
| stop | REVIEW | Shell wrapper — keep if useful |
| bin/nexph | KEEP+FIX | CLI entry — broken path `runtime/autoload.php` → should be `../autoload.php` |
| LICENSE | KEEP | |
| README.md | KEEP | Rewrite as runtime README |
| .env | GITIGNORE | Already in .gitignore |
| .env.example | KEEP | |
| .gitignore | KEEP | Update later |
| .htaccess | MOVE→examples | FPM-only, not runtime core |
| .user.ini | MOVE→examples | FPM-only |
| nexph | REVIEW | Duplicate of bin/nexph? |
| dashboard.html | MOVE→examples | Demo file |
| keygen.php | MOVE→examples | Utility/demo |
| verify-runtime.sh | MOVE→scripts/smoke | Smoke test |
| worker.php | KEEP | Queue worker entry |
| worker-daemon.php | KEEP | Queue daemon entry |
| worker.bat | REMOVE | Windows batch, likely unused |

### Deploy Configs → MOVE to `docs/deploy/`

| File | Action |
|------|--------|
| nexph-nginx.conf | MOVE→docs/deploy |
| nexph-fpm-pool.conf | MOVE→docs/deploy |
| nexph-opcache.ini | MOVE→docs/deploy |
| nexph-worker.service | MOVE→docs/deploy |

### core/ — Runtime Core (KEEP ALL)

| Directory | Status | Note |
|-----------|--------|------|
| core/Runtime/ | KEEP | Runtime engine, CLI, scheduler, supervisor |
| core/Server/ | KEEP | HTTP server, WebSocket, SSE, event loop |
| core/Http/ | KEEP | Stateless HTTP layer (router, request, response) |
| core/Support/ | KEEP | Config, cache utilities |
| core/Queue/ | KEEP | Queue system |
| core/Database/ | KEEP | DB layer, ORM, query builder |
| core/Event/ | KEEP | Event dispatcher |
| core/Log/ | KEEP | Logger |
| core/Health/ | KEEP | Health check |

### core/ — Package Candidates (MOVE later to packages)

| Directory | Status | Note |
|-----------|--------|------|
| core/Auth/ | PACKAGE | Auth, session, CSRF → `nexph/auth` |
| core/Cache/ | PACKAGE | Cache manager → `nexph/cache` |
| core/UI/ | PACKAGE | View, UI generator → `nexph/ui` |
| core/Generator/ | PACKAGE | API generator → `nexph/generator` |

### app/ → MOVE to examples

| File | Action |
|------|--------|
| app/Controllers/ | MOVE→examples/app |
| app/Jobs/*.php | MOVE→examples/queue |
| app/schedule.php | MOVE→examples |

### routes/ → MOVE to examples

| File | Action |
|------|--------|
| routes/api.php | MOVE→examples |
| routes/api/v1.php | MOVE→examples |
| routes/api/v2.php | MOVE→examples |
| routes/debug.php | MOVE→examples |
| routes/health.php | MOVE→examples |
| routes/admin.php | MOVE→examples (package candidate) |
| routes/auth.php | MOVE→examples (package candidate) |

### public/

| File | Action |
|------|--------|
| public/index.php | KEEP | FPM entry point |
| public/.htaccess | KEEP | FPM config |
| public/metrics.php | KEEP | Runtime metrics |
| public/api-explorer.html | MOVE→examples | Demo |
| public/observability.php | MOVE→examples | Demo/package candidate |
| public/index.backup.php | REMOVE | Backup |
| public/index.php.backup | REMOVE | Backup |
| public/index.optimized.php | REMOVE | Old optimization attempt |

### config/

| File | Action |
|------|--------|
| config/app.php | KEEP | Core config |
| config/cache.php | KEEP | Core config |
| config/logging.php | KEEP | Core config |
| config/ratelimit.php | KEEP | Core config |
| config/session.php | KEEP (package) | Moves with auth package |
| config/api.php | KEEP | Core config |
| config/api.json | REVIEW | Duplicate of api.php? |
| config/apcu.ini | MOVE→docs/deploy | PHP ini config |
| config/nexph-queue-worker.service | MOVE→docs/deploy | Systemd service |

### scripts/

| File | Action |
|------|--------|
| scripts/k6-users.js | MOVE→scripts/benchmarks | |
| scripts/k6-ws.js | MOVE→scripts/benchmarks | |
| scripts/k6-sse.js | MOVE→scripts/benchmarks | |
| scripts/k6-bechmark.js | MOVE→scripts/benchmarks | (typo in name) |
| scripts/benchmark.sh | MOVE→scripts/benchmarks | |
| scripts/benchmark-cache.php | MOVE→scripts/benchmarks | |
| scripts/server-test.php | MOVE→scripts/smoke | |
| scripts/ws-test.php | MOVE→scripts/smoke | |
| scripts/memory-test.php | MOVE→scripts/smoke | |
| scripts/cache.php | REVIEW | Utility or test? |
| scripts/integrate_session_auth.php | REMOVE | One-time migration script |
| scripts/nexph-worker.service | MOVE→docs/deploy | Duplicate |
| scripts/nexph-worker.supervisor.conf | MOVE→docs/deploy | |

### tests/

| Item | Action |
|------|--------|
| tests/Runtime/ (PHP tests) | KEEP | Core runtime tests |
| tests/Runtime/benchmark_results_*.json | REMOVE | Generated artifacts |
| tests/Unit/ | KEEP | Unit tests |
| tests/queue_*.php | KEEP | Queue tests |
| tests/benchmark_results/ | REMOVE | Generated artifacts |
| tests/benchmark_bootstrap.php | MOVE→scripts/benchmarks |
| tests/benchmark_profile.php | MOVE→scripts/benchmarks |
| tests/benchmark.sh | MOVE→scripts/benchmarks |
| tests/auth_test.php | KEEP (package) | Moves with auth |
| tests/auth_db_test.php | KEEP (package) | Moves with auth |
| tests/security_test.php | KEEP | |
| tests/session_security_test.php | KEEP (package) | Moves with auth |
| tests/attack_vector_test.php | KEEP | |
| tests/api_dashboard_test.php | MOVE→examples | Demo test |
| tests/htmx_integration_test.php | MOVE→examples | Demo test |
| tests/deploy.sh | MOVE→docs/deploy | |
| tests/setup-production.sh | MOVE→docs/deploy | |
| tests/test_improvements.sh | REMOVE | Old script |
| tests/run_unit_tests.sh | KEEP | |
| tests/trace_request.php | MOVE→scripts/smoke | Debug tool |
| tests/profile_api_direct.php | MOVE→scripts/benchmarks | |
| tests/profile_wrapper.php | MOVE→scripts/benchmarks | |
| tests/warm-cache.php | MOVE→scripts/smoke | |
| tests/test-cache.php | KEEP | |

### examples/ — KEEP (reorganize)

Already good structure. Keep as-is, will merge moved files here.

### metadata/ → MOVE to examples

| File | Action |
|------|--------|
| metadata/task.json | MOVE→examples/data |
| metadata/users.json | MOVE→examples/data |

### database/ → KEEP

Migrations are runtime-relevant (queue, rate limits).

### storage/ → GITIGNORE (clean)

| Item | Action |
|------|--------|
| storage/.gitignore | KEEP |
| storage/database.sqlite | GITIGNORE |
| storage/*.sqlite-shm | REMOVE (gitignore) |
| storage/*.sqlite-wal | REMOVE (gitignore) |
| storage/test_raw_engine.* | REMOVE |
| storage/sessions/* | REMOVE (60+ session files) |
| storage/logs/* | GITIGNORE |

### docs/ — HEAVY CLEANUP NEEDED

| Item | Action | Size |
|------|--------|------|
| docs/node_modules/ | REMOVE | 91MB! Should be gitignored |
| docs/.vitepress/cache/ | REMOVE | 5.6MB build cache |
| docs/.vitepress/dist/ | REVIEW | 4.6MB built site — gitignore or keep? |
| docs/runtime/ | KEEP | Actual docs |
| docs/RUNTIME_STATEFUL.md | KEEP | |
| docs/HTTP_SERVER.md | KEEP | |
| docs/CACHING.md | KEEP | |
| docs/arsitektur-stateful.md | KEEP | |
| docs/index.md | KEEP | |
| docs/package.json | KEEP | Docs build config |
| docs/package-lock.json | KEEP | |
| docs/.gitignore | UPDATE | Add node_modules, cache, dist |

### benchmark_results/ → REMOVE

Generated artifacts, already in .gitignore.

### Nested .git

`backend-engine/.git` — exists as separate repo. OK.

---

## Frontend Engine Audit

### Root Files

| File | Status | Action |
|------|--------|--------|
| autoload.php | KEEP | |
| bin/nexph | KEEP | Frontend CLI |
| LICENSE | KEEP | |
| README.md | KEEP | |
| phpunit.xml | KEEP | |
| nexph.d.ts | KEEP | TypeScript definitions |
| .gitignore | KEEP | |
| generate-docs.php | REVIEW | Keep if docs workflow active |
| .phpunit.result.cache | REMOVE | Generated |

### src/ — KEEP ALL

Runtime, Compiler, Builder, DevServer, Plugin, Autoload — all core.

### tests/ — KEEP ALL

Good test coverage.

### examples/ — KEEP

3 example files, minimal.

### docs/ — KEEP

Source docs.

### docs-site/ — REVIEW

24 files. Generated from docs/? If so, gitignore or remove.

### dist/ — REVIEW

4 files. Built output. Decide publish policy:
- If published as pre-built: KEEP
- If users build themselves: GITIGNORE

### Nested .git

`frontend-engine/.git` — exists as separate repo. OK.

---

## Summary: Immediate Actions

### REMOVE NOW (safe, no value)

1. `backend-engine/docs/node_modules/` — 91MB, should never be committed
2. `backend-engine/docs/.vitepress/cache/` — build cache
3. `backend-engine/benchmark_results/` — generated
4. `backend-engine/tests/benchmark_results/` — generated
5. `backend-engine/tests/Runtime/benchmark_results_*.json` — generated
6. `backend-engine/storage/sessions/*` — runtime data
7. `backend-engine/storage/*.sqlite-shm` — WAL files
8. `backend-engine/storage/*.sqlite-wal` — WAL files
9. `backend-engine/storage/test_raw_engine.*` — test artifacts
10. `backend-engine/public/index.backup.php` — backup
11. `backend-engine/public/index.php.backup` — backup
12. `backend-engine/public/index.optimized.php` — old attempt
13. `backend-engine/worker.bat` — unused
14. `backend-engine/scripts/integrate_session_auth.php` — one-time script
15. `backend-engine/tests/test_improvements.sh` — old script
16. `frontend-engine/.phpunit.result.cache` — generated

### FIX NOW

1. `backend-engine/bin/nexph` — path `runtime/autoload.php` → `../autoload.php`

### UPDATE .gitignore

Add to `backend-engine/.gitignore`:
```
docs/node_modules/
docs/.vitepress/cache/
docs/.vitepress/dist/
storage/sessions/
storage/logs/
benchmark_results/
tests/benchmark_results/
tests/Runtime/benchmark_results_*.json
```

---

## Next Step

Setelah audit ini di-approve, kita bisa mulai:
1. Remove garbage files
2. Update .gitignore
3. Fix bin/nexph path
4. Reorganize scripts/ → scripts/benchmarks/ + scripts/smoke/
5. Move deploy configs → docs/deploy/
