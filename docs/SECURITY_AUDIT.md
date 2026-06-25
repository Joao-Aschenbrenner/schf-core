# ðŸ”’ Security Audit Report â€” SCHF

**Data**: 14/06/2026 | **Status**: Audit ConcluÃ­do

---

## ðŸ“Š Resumo

| Categoria | Vulnerabilidades | Severidade | Status |
|-----------|-----------------|------------|--------|
| **Backend (Composer)** | 1 | Moderate | âš ï¸ Pendente |
| **Frontend (npm)** | 2 | Moderate+High | âš ï¸ Pendente |
| **Secrets (Gitleaks)** | 0 | - | âœ… Limpo |
| **SAST (PHPStan)** | 0 errors | - | âœ… Level 8 Clean |

---

## ðŸ› Vulnerabilidades Encontradas

### Backend â€” Laravel Framework

| Campo | Valor |
|-------|-------|
| **CVE** | CVE-2026-48019 |
| **TÃ­tulo** | Laravel CRLF injection in default email rule |
| **Pacote** | `laravel/framework` |
| **VersÃµes Afetadas** | >=9.0.0,<10.0.0 \| >=10.0.0,<11.0.0 \| >=11.0.0,<12.0.0 \| >=12.0.0,<12.60.0 \| >=13.0.0,<13.10.0 |
| **Severidade** | Moderate |
| **URL** | https://github.com/laravel/framework/security/advisories/GHSA-5vg9-5847-vvmq |
| **Status** | âš ï¸ AtualizaÃ§Ã£o bloqueada â€” Laravel 12+ requer PHP 8.4+ (nosso stack usa PHP 8.3) |
| **MitigaÃ§Ã£o** | NÃ£o afeta nosso caso de uso (nÃ£o usamos email rule default do Laravel). Atualizar quando migrar para PHP 8.4 |

### Frontend â€” esbuild/vite

| Campo | Valor |
|-------|-------|
| **GHSA** | GHSA-67mh-4wv8-2f99 |
| **TÃ­tulo** | esbuild enables any website to send requests to dev server |
| **Pacote** | `esbuild` <=0.28.0 (via `vite` <=6.4.1) |
| **Severidade** | High (dev server only) |
| **Status** | âš ï¸ CorreÃ§Ã£o requer Vite 8.x (breaking change) |
| **MitigaÃ§Ã£o** | Apenas afeta dev server em desenvolvimento. NÃ£o afeta produÃ§Ã£o. |

### Pacote Abandonado

| Pacote | Substituto |
|--------|------------|
| `nunomaduro/larastan` | `larastan/larastan` |

---

## âœ… VerificaÃ§Ãµes de SeguranÃ§a Realizadas

### 1. Gitleaks â€” Secret Detection
- **Resultado**: âœ… 0 secrets encontrados
- **ConfiguraÃ§Ã£o**: `.gitleaks.toml` com regras para API keys, AWS, GitHub PATs, Laravel APP_KEY, MySQL/Redis
- **Hook**: `.git/hooks/pre-commit` ativo

### 2. PHPStan â€” Static Analysis
- **Resultado**: âœ… Level 8, 0 erros
- **ConfiguraÃ§Ã£o**: `phpstan.neon` com `treatPhpDocTypesAsCertain: false`
- **Cobertura**: 15 Models, 13 Services, 14 Controllers

### 3. Laravel Security Features
- âœ… CSRF Protection (`VerifyCsrfToken` middleware)
- âœ… Authentication (Sanctum tokens)
- âœ… Authorization (Spatie Permissions + Roles)
- âœ… Rate Limiting (`ThrottleRequests`)
- âœ… Input Validation (Form Requests)
- âœ… SQL Injection Prevention (Eloquent ORM)
- âœ… XSS Prevention (Blade/React escaping)
- âœ… Audit Trail (Spatie Activity Log)

### 4. Frontend Security Features
- âœ… No hardcoded secrets in client
- âœ… API URL configurable (environment variable)
- âœ… Token-based auth (Sanctum)
- âœ… MSW handlers don't expose real API in tests

---

## ðŸŽ¯ AÃ§Ãµes Recomendadas

| Prioridade | AÃ§Ã£o | Status |
|------------|------|--------|
| Alta | Atualizar Laravel quando PHP 8.4+ disponÃ­vel | Pendente |
| MÃ©dia | Atualizar Vite para v8.x (breaking change) | Pendente |
| Baixa | Substituir `nunomaduro/larastan` por `larastan/larastan` | Pendente |
| Baixa | Instalar Semgrep para SAST contÃ­nuo | Pendente |

---

## ðŸ“š ReferÃªncias

- **Vault ENIAC METRON**: `13-CENTRAL-Seguranca-Cyber/Pesquisa-Seguranca.md`
- **OWASP Top 10**: https://owasp.org/www-project-top-ten/
- **Laravel Security**: https://laravel.com/docs/master/security

