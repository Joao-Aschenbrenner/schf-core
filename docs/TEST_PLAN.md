# ðŸ§ª PLANO DE TESTES â€” SCHF

**VersÃ£o**: 1.0 | **Data**: 14/06/2026 | **Status**: Em ExecuÃ§Ã£o

---

## ðŸ“‹ SumÃ¡rio Executivo

Plano de testes abrangente baseado na pesquisa de 100+ ferramentas do vault ENIAC METRON (05-CENTRAL-Qualidade-Testes), adaptado para o stack Laravel 11 + React/TypeScript + Tauri v2.

| Fase | DescriÃ§Ã£o | Status | Meta |
|------|-----------|--------|------|
| 1 | Infraestrutura de Testes | âœ… | Vitest + Pest + Gitleaks |
| 2 | Testes UnitÃ¡rios Backend | ðŸ”„ | 80%+ cobertura |
| 3 | Testes UnitÃ¡rios Frontend | ðŸ”„ | 80%+ cobertura |
| 4 | Testes de IntegraÃ§Ã£o | â³ | 80%+ cobertura |
| 5 | Testes E2E | â³ | Fluxos crÃ­ticos |
| 6 | Testes de SeguranÃ§a | â³ | Zero vulnerabilidades |
| 7 | Testes de Performance | â³ | P95 < 500ms |
| 8 | Mutation Testing | â³ | 80%+ mutation score |
| 9 | Installer Validation | â³ | 100% cobertura |
| 10 | DocumentaÃ§Ã£o | â³ | Completa |

---

## ðŸ—ï¸ Fase 1: Infraestrutura de Testes

### 1.1 Backend â€” Pest PHP + Laravel

**Ferramenta**: Pest PHP 2.x (extensÃ£o do PHPUnit)
**Por quÃª**: Sintaxe expressiva, integrado ao Laravel, suporte nativo a mocking

**ConfiguraÃ§Ã£o**:
```bash
# JÃ¡ configurado em composer.json
composer test           # Executa todos os testes
composer test:coverage  # Com cobertura de cÃ³digo
composer analyse        # PHPStan Level 8
composer pint           # Code style check
composer pint:fix       # Code style auto-fix
```

**Arquivos**:
- `backend/phpstan.neon` â€” PHPStan Level 8 (CLEAN, 0 erros)
- `backend/tests/Pest.php` â€” ConfiguraÃ§Ã£o base do Pest
- `backend/tests/TestCase.php` â€” Classe base para testes

### 1.2 Frontend â€” Vitest + React Testing Library + MSW

**Ferramentas**: Vitest + @testing-library/react + MSW + c8
**Por quÃª**: Vitest Ã© 100x mais rÃ¡pido que Jest em projetos Vite, MSW intercepta API no nÃ­vel de rede

**ConfiguraÃ§Ã£o**:
```bash
cd frontend
npm test              # Executa testes
npm run test:coverage # Com cobertura (c8/V8)
npm run test:ui       # Interface grÃ¡fica
npm run test:watch    # Watch mode
```

**Arquivos**:
- `frontend/vite.config.ts` â€” ConfiguraÃ§Ã£o Vitest (jsdom, thresholds 80%)
- `frontend/src/test/setup.ts` â€” Mocks globais
- `frontend/src/test/handlers.ts` â€” Handlers MSW para todas as APIs
- `frontend/src/test/server.ts` â€” Servidor MSW

### 1.3 SeguranÃ§a â€” Gitleaks

**Ferramenta**: Gitleaks 8.x
**Por quÃª**: Previne secrets no git, pre-commit hook

**Arquivos**:
- `.gitleaks.toml` â€” Regras: API keys, AWS, GitHub PATs, Laravel APP_KEY, MySQL/Redis
- `.git/hooks/pre-commit` â€” Hook automÃ¡tico

**Uso**:
```bash
gitleaks detect --source . --config .gitleaks.toml --no-git
```

---

## ðŸ§ª Fase 2: Testes UnitÃ¡rios Backend

### 2.1 Models (15 models)

| Model | Testes | Prioridade | Tipo |
|-------|--------|------------|------|
| User | Factory, Scopes, Relations | Alta | CRUD |
| NfeEntrada | Factory, Scopes, Accessors | Alta | DomÃ­nio |
| NfeSaida | Factory, Scopes, Accessors | Alta | DomÃ­nio |
| ContaPagar | Factory, Scopes, Calculations | CrÃ­tica | Financeiro |
| ContaReceber | Factory, Scopes, Calculations | CrÃ­tica | Financeiro |
| LancamentoContabil | Factory, Scopes | Alta | ContÃ¡bil |
| Convenio | Factory, Scopes, Relations | MÃ©dia | Cadastro |
| Fornecedor | Factory, Scopes, Relations | MÃ©dia | Cadastro |
| PlanoConta | Factory, Scopes, Tree | Alta | Plano |
| CentroCusto | Factory, Scopes, Tree | Alta | Plano |
| Boleto | Factory, Scopes | MÃ©dia | Financeiro |
| ContaBancaria | Factory, Scopes | MÃ©dia | Cadastro |
| Conciliacao | Factory, Scopes | Alta | DomÃ­nio |
| PreLancamento | Factory, Scopes | Alta | DomÃ­nio |
| LancamentoRecorrente | Factory, Scopes | MÃ©dia | AutomaÃ§Ã£o |

**Estrutura de teste**:
```php
test('factory creates valid model', function () {
    $model = Model::factory()->create();
    expect($model)->toBeInstanceOf(Model::class);
    expect($model->nome)->not->toBeEmpty();
});

test('scope filters active records', function () {
    Model::factory()->count(5)->create(['ativo' => true]);
    Model::factory()->count(3)->create(['ativo' => false]);
    expect(Model::ativo()->count())->toBe(5);
});
```

### 2.2 Services (13 services)

| Service | Testes | Prioridade | Responsabilidade |
|---------|--------|------------|------------------|
| NfeService | ValidaÃ§Ã£o, Processamento | CrÃ­tica | NF-e XML |
| ContaPagarService | CRUD, ValidaÃ§Ã£o, CÃ¡lculos | CrÃ­tica | Contas a pagar |
| ContaReceberService | CRUD, ValidaÃ§Ã£o, CÃ¡lculos | CrÃ­tica | Contas a receber |
| LancamentoContabilService | Dupla escritura | CrÃ­tica | Contabilidade |
| ConciliacaoService | OFX import, Match | CrÃ­tica | ConciliaÃ§Ã£o |
| FornecedorService | CRUD, ValidaÃ§Ã£o | MÃ©dia | Cadastro |
| ConvenioService | CRUD, ValidaÃ§Ã£o | MÃ©dia | Cadastro |
| PlanoContaService | CRUD, Ãrvore | Alta | Plano de contas |
| CentroCustoService | CRUD, Ãrvore | Alta | Centro de custos |
| BoletoService | GeraÃ§Ã£o, Consulta | MÃ©dia | Boletos |
| ExportService | PDF, Excel | MÃ©dia | RelatÃ³rios |
| DashboardService | KPIs, MÃ©tricas | Alta | Dashboard |
| AuditTrailService | Logs, Rastreabilidade | CrÃ­tica | Auditoria |

### 2.3 Controllers (14 controllers)

| Controller | Testes | Prioridade |
|-----------|--------|------------|
| AuthController | Login, Logout, Profile | CrÃ­tica |
| NfeController | CRUD, ValidaÃ§Ã£o, XML | CrÃ­tica |
| ContaPagarController | CRUD, ValidaÃ§Ã£o, Pagamento | CrÃ­tica |
| ContaReceberController | CRUD, ValidaÃ§Ã£o, Recebimento | CrÃ­tica |
| ConciliacaoController | Import, Match, Manual | CrÃ­tica |
| LancamentoContabilController | CRUD, Dupla escrita | CrÃ­tica |
| FornecedorController | CRUD, ValidaÃ§Ã£o | MÃ©dia |
| ConvenioController | CRUD, ValidaÃ§Ã£o | MÃ©dia |
| PlanoContaController | CRUD, Ãrvore | Alta |
| CentroCustoController | CRUD, Ãrvore | Alta |
| BoletoController | Gerar, Consultar | MÃ©dia |
| DashboardController | KPIs, MÃ©tricas | Alta |
| ReportController | 5 relatÃ³rios | MÃ©dia |
| AuditTrailController | Logs, Filtros | CrÃ­tica |

---

## ðŸ§ª Fase 3: Testes UnitÃ¡rios Frontend

### 3.1 Components (20+ components)

| Component | Testes | Prioridade |
|-----------|--------|------------|
| NfeForm | Render, Submit, Validation | Alta |
| ContaPagarForm | Render, Submit, Validation | CrÃ­tica |
| ContaReceberForm | Render, Submit, Validation | CrÃ­tica |
| ConciliacaoTable | Render, Sort, Filter | Alta |
| DashboardCards | Render, KPIs | Alta |
| LancamentoTable | Render, Sort, Filter | Alta |
| FornecedorForm | Render, Submit, Validation | MÃ©dia |
| ConvenioForm | Render, Submit, Validation | MÃ©dia |
| PlanoContaTree | Render, Expand, Select | Alta |
| CentroCustoTree | Render, Expand, Select | Alta |
| BoletoForm | Render, Submit, Validation | MÃ©dia |
| ReportTabs | Tab switching | MÃ©dia |
| AuditLogTable | Render, Pagination | CrÃ­tica |
| PreLancamentoForm | Render, Submit | Alta |
| CronogramaTable | Render, Projection | Alta |
| SearchInput | Debounce, Clear | MÃ©dia |
| DateRangePicker | Render, Select | MÃ©dia |
| StatusBadge | Render, Variants | MÃ©dia |
| ConfirmDialog | Render, Actions | MÃ©dia |
| DataTable | Render, Sort, Paginate | Alta |

### 3.2 Pages (12 pages)

| Page | Testes | Prioridade |
|------|--------|------------|
| LoginPage | Render, Submit, Redirect | CrÃ­tica |
| DashboardPage | Render, KPIs, Loading | Alta |
| NfeEntradaPage | Render, CRUD | Alta |
| NfeSaidaPage | Render, CRUD | Alta |
| ContasPagarPage | Render, CRUD | CrÃ­tica |
| ContasReceberPage | Render, CRUD | CrÃ­tica |
| ConciliacaoPage | Render, Import, Match | CrÃ­tica |
| LancamentosPage | Render, CRUD | Alta |
| FornecedoresPage | Render, CRUD | MÃ©dia |
| ConveniosPage | Render, CRUD | MÃ©dia |
| CronogramaPage | Render, Projection | Alta |
| ReportsPage | Render, Tabs, Export | MÃ©dia |

### 3.3 Hooks & Services

| Hook/Service | Testes | Prioridade |
|--------------|--------|------------|
| useAuth | Login, Logout, Token | CrÃ­tica |
| useNfes | Query, Mutation | Alta |
| useContasPagar | Query, Mutation | CrÃ­tica |
| useContasReceber | Query, Mutation | CrÃ­tica |
| useConciliacao | Query, Mutation | CrÃ­tica |
| useLancamentos | Query, Mutation | Alta |
| useFornecedores | Query, Mutation | MÃ©dia |
| useConvenios | Query, Mutation | MÃ©dia |
| apiClient | Interceptors, Errors | CrÃ­tica |

---

## ðŸ”— Fase 4: Testes de IntegraÃ§Ã£o

### 4.1 Abordagem

**Ferramenta**: Pest PHP com SQLite in-memory (Testcontainers)
**Por quÃª**: Testes rÃ¡pidos, isolados, sem MySQL

**ConfiguraÃ§Ã£o**:
```php
// TestCase.php
protected function defineDatabaseMigrations(): void
{
    $this->app['config']->set('database.default', 'testing');
    $this->app['config']->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);
}
```

### 4.2 Fluxos de IntegraÃ§Ã£o

| Fluxo | Endpoint | MÃ©todos | Prioridade |
|-------|----------|---------|------------|
| Auth Flow | /api/auth/* | POST, GET, DELETE | CrÃ­tica |
| NF-e Flow | /api/nfes/* | CRUD + ValidaÃ§Ã£o | CrÃ­tica |
| Pagar Flow | /api/contas-pagar/* | CRUD + Pagamento | CrÃ­tica |
| Receber Flow | /api/contas-receber/* | CRUD + Recebimento | CrÃ­tica |
| ConciliaÃ§Ã£o | /api/conciliacao/* | Import + Match | CrÃ­tica |
| ContÃ¡bil | /api/lancamentos/* | CRUD + Dupla Escrita | CrÃ­tica |
| Cadastros | /api/fornecedores/* | CRUD | MÃ©dia |
| Cadastros | /api/convenios/* | CRUD | MÃ©dia |
| Plano | /api/plano-contas/* | CRUD + Ãrvore | Alta |
| Centro | /api/centro-custos/* | CRUD + Ãrvore | Alta |
| Dashboard | /api/dashboard | GET | Alta |
| Reports | /api/reports/* | GET | MÃ©dia |
| Audit | /api/audit-trail/* | GET | CrÃ­tica |

---

## ðŸŒ Fase 5: Testes E2E (Playwright)

### 5.1 InstalaÃ§Ã£o

```bash
cd frontend
npm install -D @playwright/test
npx playwright install chromium
```

### 5.2 Fluxos E2E

| Fluxo | DescriÃ§Ã£o | Prioridade |
|-------|-----------|------------|
| Login | Abrir â†’ Login â†’ Dashboard | CrÃ­tica |
| NF-e | Criar â†’ Validar â†’ Confirmar â†’ Imprimir | CrÃ­tica |
| Pagar | Criar â†’ Aprovar â†’ Pagar â†’ Baixar | CrÃ­tica |
| Receber | Criar â†’ Confirmar â†’ Receber â†’ Baixar | CrÃ­tica |
| ConciliaÃ§Ã£o | Importar OFX â†’ Match â†’ Confirmar | CrÃ­tica |
| RelatÃ³rios | Abrir â†’ Filtrar â†’ Exportar | MÃ©dia |
| Cadastros | CRUD completo | MÃ©dia |
| Cronograma | Visualizar projeÃ§Ãµes | Alta |

### 5.3 ConfiguraÃ§Ã£o Playwright

```typescript
// playwright.config.ts
export default defineConfig({
  testDir: './e2e',
  timeout: 30000,
  retries: 2,
  use: {
    baseURL: 'http://localhost:3000',
    screenshot: 'on-failure',
    trace: 'retain-on-failure',
  },
  projects: [
    { name: 'chromium', use: { browserName: 'chromium' } },
  ],
});
```

---

## ðŸ”’ Fase 6: Testes de SeguranÃ§a

### 6.1 SAST â€” Semgrep

**Ferramenta**: Semgrep (Static Application Security Testing)
**Regras**: Laravel, PHP, React, TypeScript

```bash
# Backend
semgrep --config "p/php" --config "p/laravel" backend/

# Frontend
semgrep --config "p/typescript-react" frontend/
```

### 6.2 DAST â€” OWASP ZAP

**Ferramenta**: OWASP ZAP (Dynamic Application Security Testing)
**Alvo**: API endpoints em execuÃ§Ã£o

```bash
zap-cli quick-scan http://localhost:8080/api/
```

### 6.3 Dependency Check

```bash
# Backend
composer audit

# Frontend
npm audit
```

### 6.4 VerificaÃ§Ãµes de SeguranÃ§a

| VerificaÃ§Ã£o | FerrÃªncia | Status |
|-------------|-----------|--------|
| SQL Injection | Semgrep + Manual | â³ |
| XSS | Semgrep + ZAP | â³ |
| CSRF | Laravel VerifyCsrfToken | âœ… |
| Authentication | Sanctum + Tests | âœ… |
| Authorization | Spatie Permissions + Tests | âœ… |
| Rate Limiting | Laravel Throttle | âœ… |
| Input Validation | Form Requests | âœ… |
| Secrets | Gitleaks | âœ… |

---

## âš¡ Fase 7: Testes de Performance

### 7.1 k6 â€” Load Testing REST

**Ferramenta**: k6 (Grafana)
**Alvo**: API endpoints

```javascript
// scripts/perf-rest.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '1m', target: 100 },   // Ramp up
    { duration: '5m', target: 100 },   // Steady state
    { duration: '1m', target: 0 },     // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],  // 95% under 500ms
    http_req_failed: ['rate<0.01'],    // <1% errors
  },
};
```

### 7.2 Benchmarks

| Endpoint | Target P95 | Target P99 | Max RPS |
|----------|------------|------------|---------|
| GET /api/dashboard | <200ms | <500ms | 100 |
| GET /api/nfes | <300ms | <800ms | 50 |
| GET /api/contas-pagar | <300ms | <800ms | 50 |
| POST /api/auth/login | <500ms | <1000ms | 20 |
| GET /api/reports/* | <2000ms | <5000ms | 10 |

---

## ðŸ§¬ Fase 8: Mutation Testing

### 8.1 Backend â€” Infection PHP

**Ferramenta**: Infection PHP
**Meta**: 80%+ mutation score

```bash
cd backend
composer require --dev infection/infection
./vendor/bin/infection --threads=4 --min-msi=80
```

### 8.2 Frontend â€” Stryker

**Ferramenta**: Stryker Mutator
**Meta**: 80%+ mutation score

```bash
cd frontend
npm install -D @stryker-mutator/core @stryker-mutator/vitest-runner
npx stryker run
```

---

## ðŸ“¦ Fase 9: Installer Validation

### 9.1 NSIS Installer Tests

| Teste | DescriÃ§Ã£o | Status |
|-------|-----------|--------|
| Install | InstalaÃ§Ã£o limpa | â³ |
| Upgrade | AtualizaÃ§Ã£o sobre versÃ£o anterior | â³ |
| Uninstall | DesinstalaÃ§Ã£o completa | â³ |
| Repair | Reparo de instalaÃ§Ã£o corrompida | â³ |
| Prerequisites | Verifica Docker, WSL, ports | â³ |
| Silent | InstalaÃ§Ã£o silenciosa | â³ |

### 9.2 MSI Installer Tests

| Teste | DescriÃ§Ã£o | Status |
|-------|-----------|--------|
| Install | InstalaÃ§Ã£o via MSI | â³ |
| Group Policy | Deploy via GPO | â³ |
| Uninstall | DesinstalaÃ§Ã£o via MSI | â³ |

---

## ðŸ“š Fase 10: DocumentaÃ§Ã£o

### 10.1 Arquivos

| Arquivo | DescriÃ§Ã£o | Status |
|---------|-----------|--------|
| TEST_PLAN.md | Este documento | âœ… |
| TEST_MATRIX.md | Matriz de cobertura | â³ |
| COVERAGE_REPORT.md | RelatÃ³rio de cobertura | â³ |
| SECURITY_AUDIT.md | Auditoria de seguranÃ§a | â³ |
| INSTALLER_GUIDE.md | Guia do instalador | â³ |
| TROUBLESHOOTING.md | SoluÃ§Ã£o de problemas | â³ |

---

## ðŸ”„ Comandos Ãšteis

### Backend
```bash
# Rodar todos os testes
docker exec -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: schf-backend php artisan test

# Com cobertura
docker exec -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: schf-backend php artisan test --coverage

# PHPStan
docker exec schf-backend vendor/bin/phpstan analyse --memory-limit=2G

# Code style
docker exec schf-backend vendor/bin/pint --test
```

### Frontend
```bash
cd frontend

# Rodar testes
npm test

# Com cobertura
npm run test:coverage

# Watch mode
npm run test:watch

# E2E
npx playwright test
```

### SeguranÃ§a
```bash
# Gitleaks
gitleaks detect --source . --config .gitleaks.toml --no-git

# Semgrep
semgrep --config "p/php" backend/
semgrep --config "p/typescript-react" frontend/

# Dependency check
composer audit  # Backend
npm audit       # Frontend
```

---

## ðŸ“Š MÃ©tricas de Sucesso

| MÃ©trica | Target | Atual |
|---------|--------|-------|
| Cobertura Backend | 80%+ | 0% (inÃ­cio) |
| Cobertura Frontend | 80%+ | 0% (inÃ­cio) |
| PHPStan Level | 8 | âœ… L8 Clean |
| Mutation Score | 80%+ | N/A |
| Testes E2E | 10+ fluxos | 0 |
| Vulnerabilidades | 0 crÃ­ticas | â³ |
| Performance P95 | <500ms | â³ |

---

## ðŸŽ¯ PrÃ³ximos Passos Imediatos

1. **FASE 2**: Criar testes unitÃ¡rios para os 15 Models (factory, scopes, relations)
2. **FASE 2**: Criar testes para os 13 Services (business logic, calculations)
3. **FASE 2**: Criar testes para os 14 Controllers (validation, auth, responses)
4. **FASE 3**: Criar testes para os 20+ Components (render, props, variants)
5. **FASE 3**: Criar testes para as 12 Pages (mount, data loading, interactions)
6. **FASE 3**: Criar testes para os Hooks/Services (query states, mutations)
7. **FASE 4**: Configurar SQLite in-memory para testes de integraÃ§Ã£o
8. **FASE 4**: Criar fluxos de integraÃ§Ã£o para endpoints crÃ­ticos
9. **FASE 5**: Instalar Playwright e criar fluxos E2E
10. **FASE 6-10**: Executar conforme o progresso

---

## ðŸ“š ReferÃªncias

- **Vault ENIAC METRON**: `05-CENTRAL-Qualidade-Testes/Pesquisa-Testes-Automatizados.md` (100+ ferramentas)
- **Vault ENIAC METRON**: `05-CENTRAL-Qualidade-Testes/QA-Testing-Qualidade.md` (Regras QA)
- **MASTER_SPRINT_FINAL.md**: Fases obrigatÃ³rias 0-9
- **Ferramentas adaptadas**: Java 21 â†’ Laravel 11 + React/TypeScript

