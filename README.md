# Nexph

Workspace monorepo for Nexph runtime engines.

## Engines

| Engine | Description |
|--------|-------------|
| [backend-engine](./backend-engine/) | Stateful PHP HTTP/WS/SSE server runtime |
| [frontend-engine](./frontend-engine/) | PHP-to-static frontend compiler |

## CLI

Both engines share the `nexph` CLI name:

```bash
# Backend
cd backend-engine
php bin/nexph serve
php bin/nexph run file.php
php bin/nexph install nexph/auth

# Frontend
cd frontend-engine
php bin/nexph build src/App.php
php bin/nexph dev src/App.php
```

## Plans

- [ROADMAP.md](./ROADMAP.md) — Feature roadmap
- [REPO_CLEANUP_PLAN.md](./REPO_CLEANUP_PLAN.md) — Repo restructuring plan
- [LOADER_PLAN.md](./LOADER_PLAN.md) — Package manager & runtime loader plan
- [AUDIT_REPORT.md](./AUDIT_REPORT.md) — Workspace audit results

## License

MIT
