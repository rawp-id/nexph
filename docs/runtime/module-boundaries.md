# Module Boundary Rules

## Directory Roles

| Directory | Role | `nexph.json` required | Auto-discovered |
|-----------|------|----------------------|-----------------|
| `src/` | Core internal runtime | NO | NO |
| `bags/local/` | Local dev packages | YES | YES |
| `bags/installed/` | Installed packages (via registry) | YES | YES |
| `modules/` | App-level modules | YES | YES |
| `nexph_modules/` | Legacy alias for installed | YES | YES |

## Rules

1. `src/*` is internal runtime — never treated as a package, no manifest needed
2. `bags/local/*` must have `nexph.json` — these are package candidates being developed locally
3. `bags/installed/*` must have `nexph.json` — installed by `nexph install`
4. `modules/*` must have `nexph.json` — app-level custom modules
5. Root `nexph.json` is the project/app manifest, not a package manifest
6. Duplicate package names across any discovery path are rejected

## Discovery Priority

```
1. bags/local/*       (local dev packages — highest priority)
2. bags/installed/*   (registry-installed packages)
3. modules/*          (app-level modules)
4. nexph_modules/*    (legacy compat)
```

## Source Labels

| Source | Meaning |
|--------|---------|
| `core` | Internal `src/` — not a package |
| `local` | From `bags/local/` |
| `installed` | From `bags/installed/` |
| `app` | From `modules/` |
| `composer` | Via Composer bridge |
