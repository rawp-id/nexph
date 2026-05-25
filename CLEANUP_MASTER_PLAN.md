# Nexph Runtime Distribution Cleanup Master Plan

## 1. Goal
runtime-only distribution + publish ready

## 2. Target Structure
backend-engine, frontend-engine, CLI, package manager

## 3. Audit Findings
ringkasan root/backend/frontend

## 4. Immediate Safe Cleanup
remove generated/cache/backup

## 5. Fix Now
bin/nexph path, gitignore

## 6. Keep/Move/Delete Map
backend + frontend

## 7. Execution Phases
phase 0..n checklist

## 8. Do Not Touch Yet
core/Auth, Cache, UI, Generator until package manager ready

## 9. Verification
lint, smoke, k6, docs links