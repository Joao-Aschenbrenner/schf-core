# ADMIN_ACCESS_REPORT

**Data:** 2026-06-18
**Versao:** 1.0

---

## Resumo

Usuario administrativo criado e validado com sucesso para acesso ao sistema SCHF.

---

## Usuario Criado

| Campo | Valor |
|-------|-------|
| **Nome** | Administrador Sistema |
| **Email** | admin@hospital.local |
| **Senha Inicial** | ChangeMe#2026! |
| **Status** | Ativo |
| **Perfil** | super_admin |
| **Permissoes** | 16 permissoes (todas) |

---

## Credenciais de Acesso

```
URL: http://localhost:9080 (via nginx) ou http://localhost:1420 (frontend direto)
Email: admin@hospital.local
Senha: ChangeMe#2026!
```

---

## Permissoes Atribuidas (16 total)

### Sistema
- view_dashboard
- manage_users
- manage_backups
- manage_historico
- export_data

### Financeiro
- manage_payables
- manage_receivables
- manage_provisions
- manage_bank_accounts
- manage_cash_registers
- manage_bank_investments
- manage_bank_operations

### Cadastros
- manage_suppliers
- manage_health_plans
- manage_expense_categories
- manage_bank_accounts

### Documentos
- manage_nfe
- manage_dda
- manage_pre_launches
- manage_conciliation
- manage_reports
- manage_pre_launches
- view_audit_trail

### Administrativo
- manage_users
- manage_backups
- manage_historico
- export_data

---

## Comandos Disponiveis

### Criar/Atualizar Admin
```bash
php artisan create:admin
# Opcoes:
#   --email=admin@hospital.local
#   --name="Administrador Sistema"
#   --password=ChangeMe#2026!
#   --force
```

### Executar Seeders
```bash
php artisan db:seed --class=AdminDemoSeeder
php artisan db:seed --class=RolePermissionSeeder
```

---

## Validacao Realizada

| Verificacao | Status |
|-------------|--------|
| Usuario criado no banco | OK |
| Senha hasheada (bcrypt) | OK |
| Role super_admin atribuida | OK |
| 16 permissoes sincronizadas | OK |
| Token Sanctum gerado | OK |
| Login via API funcional | OK |
| Usuario ativo | OK |

---

## Seguranca

- Senha armazenada com **bcrypt** (cost 10)
- Token de acesso via **Laravel Sanctum** (expiracao configuravel)
- Acesso apenas via **HTTPS** em producao
- Auditoria de login via **Spatie ActivityLog**

---

## Proximos Passos Recomendados

1. [ ] Alterar senha padrao no primeiro acesso
2. [ ] Configurar 2FA (Two-Factor Authentication)
3. [ ] Definir politica de expiracao de senha
4. [ ] Configurar logs de acesso suspeito

---

**Criado em:** 2026-06-18
**Status:** VALIDADO E PRONTO PARA USO

