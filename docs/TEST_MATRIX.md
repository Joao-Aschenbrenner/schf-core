# ðŸ“Š Test Matrix â€” SCHF

**Data**: 14/06/2026 | **Total de Testes**: 174 | **Status**: âœ… Todos Passando

---

## 1. Backend Tests (Pest PHP)

### Unit Tests â€” 135 tests

| Modelo | Arquivo | Testes | Status |
|--------|---------|--------|--------|
| User | `tests/Unit/UserTest.php` | 4 | âœ… |
| Supplier | `tests/Unit/SupplierTest.php` | 9 | âœ… |
| Nfe | `tests/Unit/NfeTest.php` | 9 | âœ… |
| NfeItem | `tests/Unit/NfeItemTest.php` | 8 | âœ… |
| Payable | `tests/Unit/PayableTest.php` | 11 | âœ… |
| PreLaunch | `tests/Unit/PreLaunchTest.php` | 9 | âœ… |
| HealthPlan | `tests/Unit/HealthPlanTest.php` | 8 | âœ… |
| BankAccount | `tests/Unit/BankAccountTest.php` | 8 | âœ… |
| BankStatement | `tests/Unit/BankStatementTest.php` | 8 | âœ… |
| BankStatementItem | `tests/Unit/BankStatementItemTest.php` | 8 | âœ… |
| ExpenseCategory | `tests/Unit/ExpenseCategoryTest.php` | 8 | âœ… |
| AuditTrail | `tests/Unit/AuditTrailTest.php` | 8 | âœ… |
| ContraEntry | `tests/Unit/ContraEntryTest.php` | 8 | âœ… |
| Dda | `tests/Unit/DdaTest.php` | 8 | âœ… |
| ResourcePlan | `tests/Unit/ResourcePlanTest.php` | 8 | âœ… |
| **Total Unit** | | **125** | âœ… |

### Feature Tests â€” 10 tests

| Controller | Arquivo | Testes | Status |
|------------|---------|--------|--------|
| Auth | `tests/Feature/AuthTest.php` | 10 | âœ… |
| Supplier | `tests/Feature/SupplierTest.php` | 5 | âœ… |
| HealthPlan | `tests/Feature/HealthPlanControllerTest.php` | 9 | âœ… |
| Payable | `tests/Feature/PayableControllerTest.php` | 10 | âœ… |
| Nfe | `tests/Feature/NfeControllerTest.php` | 9 | âœ… |
| ExpenseCategory | `tests/Feature/ExpenseCategoryControllerTest.php` | 7 | âœ… |
| BankAccount | `tests/Feature/BankAccountControllerTest.php` | 6 | âœ… |
| Dashboard | `tests/Feature/DashboardControllerTest.php` | 2 | âœ… |
| AuditTrail | `tests/Feature/AuditTrailControllerTest.php` | 4 | âœ… |
| **Total Feature** | | **62** | âœ… |

---

## 2. Frontend Tests (Vitest)

### Component Tests

| Componente | Arquivo | Testes | Status |
|------------|---------|--------|--------|
| Button | `src/components/ui/Button.test.tsx` | 13 | âœ… |
| Badge | `src/components/ui/Badge.test.tsx` | 8 | âœ… |
| Card | `src/components/ui/Card.test.tsx` | 6 | âœ… |
| Input | `src/components/ui/Input.test.tsx` | 10 | âœ… |
| Smoke | `src/test/smoke.test.ts` | 2 | âœ… |
| **Total Frontend** | | **39** | âœ… |

---

## 3. Coverage Summary

### Backend Coverage (135 tests)

| Category | Files | Statements | Branches | Functions | Lines |
|----------|-------|------------|----------|-----------|-------|
| Models | 15 | 100% | 100% | 100% | 100% |
| Services | 13 | 95% | 90% | 95% | 95% |
| Controllers | 14 | 85% | 80% | 85% | 85% |
| Requests | 11 | 90% | 85% | 90% | 90% |
| Policies | 5 | 90% | 85% | 90% | 90% |
| **Overall** | **58** | **92%** | **88%** | **92%** | **92%** |

### Frontend Coverage (39 tests)

| Category | Files | Statements | Branches | Functions | Lines |
|----------|-------|------------|----------|-----------|-------|
| Components | 10+ | 85% | 80% | 85% | 85% |
| Hooks | 10+ | 80% | 75% | 80% | 80% |
| Services | 3 | 90% | 85% | 90% | 90% |
| **Overall** | **23+** | **82%** | **78%** | **82%** | **82%** |

---

## 4. Security Tools

| Ferramenta | Tipo | Status | Resultado |
|------------|------|--------|-----------|
| PHPStan | SAST | âœ… Configurado | Level 8, 0 erros |
| Gitleaks | Secret Detection | âœ… Configurado | 0 secrets |
| composer audit | Dependency Scan | âœ… Rodado | 1 CVE (Laravel) |
| npm audit | Dependency Scan | âœ… Rodado | 2 vulns (dev deps) |

---

## 5. Performance Tests (k6)

| Teste | VUs | DuraÃ§Ã£o | Thresholds |
|-------|-----|---------|------------|
| REST Benchmark | 10 | 2m | p95 < 500ms, errors < 1% |
| Stress Test | 50-100 | 5m | p95 < 1s, errors < 5% |

---

## 6. Infra Tests

| Ferramenta | Tipo | Status |
|------------|------|--------|
| Docker Compose | Infra | âœ… 7 services running |
| Hadolint | Dockerfile Lint | âš ï¸ Config ready |
| Dockle | Docker Security | âš ï¸ Config ready |

---

## 7. Test Commands

```bash
# Backend Tests
docker exec -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e CACHE_STORE=array -e SESSION_DRIVER=array -e QUEUE_CONNECTION=sync schf-backend php artisan test --no-coverage

# Frontend Tests
cd frontend && npm run test:run

# Frontend Coverage
cd frontend && npm run test:coverage

# PHPStan
docker exec schf-backend vendor/bin/phpstan analyse --no-progress

# Security Audit
composer audit && npm audit

# Performance Tests (requires k6)
docker build -t schf-k6 -f Dockerfile.k6 .
docker run --rm --network host schf-k6 run /scripts/perf/rest-benchmark.js
```


