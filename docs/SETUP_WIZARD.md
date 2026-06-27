# Setup Wizard

O Setup Wizard é o fluxo de configuração inicial do SCHF Core. Ele é executado automaticamente na primeira instalação quando nenhuma organização existe no sistema.

## Fluxo

O wizard possui **3 etapas**:

```
[1. Instituição] → [2. Administrador] → [3. Concluído]
```

## Etapa 1 — Instituição

**Endpoint**: `GET /api/setup/status`

Se `is_configured=false`, o frontend redireciona para `/setup-wizard`.

**Endpoint**: `POST /api/setup/organization`

Dados aceitos:

```json
{
    "name": "Hospital Exemplo",
    "cnpj": "12.345.678/0001-90",
    "city": "São Paulo",
    "state": "SP",
    "email": "contato@hospital-exemplo.com",
    "phone": "(11) 99999-9999",
    "address": "Rua Exemplo, 123 - Centro"
}
```

**Resposta**:

```json
{
    "organization": {
        "id": 1,
        "name": "Hospital Exemplo",
        "cnpj": "12.345.678/0001-90",
        ...
    }
}
```

**Validações**:
- `name`: obrigatório, max 255 caracteres
- `cnpj`: opcional, exatamente 18 caracteres (formato XX.XXX.XXX/XXXX-XX)
- `city`: opcional, max 100 caracteres
- `state`: opcional, exatamente 2 caracteres
- `email`: opcional, formato de e-mail válido
- `phone`: opcional, max 20 caracteres
- `address`: opcional

**Efeitos colaterais**:
- Cria organização com `is_primary=true` e `is_active=true`
- Dispara evento `OrganizationCreated`

## Etapa 2 — Administrador

**Endpoint**: `POST /api/setup/admin`

Dados aceitos:

```json
{
    "name": "João Silva",
    "email": "admin@hospital-exemplo.com",
    "password": "sua_senha_aqui",
    "password_confirmation": "sua_senha_aqui"
}
```

**Resposta**:

```json
{
    "user": {
        "id": 1,
        "name": "João Silva",
        "email": "admin@hospital-exemplo.com",
        "organization_id": 1,
        "is_master": true,
        "is_system_admin": true,
        "is_active": true
    },
    "master_token": "abc123..."
}
```

**Validações**:
- `name`: obrigatório, max 255 caracteres
- `email`: obrigatório, único na tabela `users`, formato válido
- `password`: obrigatório, min 8 caracteres, confirmado

**Efeitos colaterais**:
- Cria usuário com `is_master=true`, `is_system_admin=true`, `organization_id` aponta para org primária
- Associa role `super_admin` ao usuário
- Gera `master_token` para operações críticas
- Dispara eventos `UserCreated` e `UserAssignedRole`

## Etapa 3 — Conclusão

**Endpoint**: `POST /api/setup/complete`

Não requer dados.

**Resposta**:

```json
{
    "message": "Configuração inicial concluída com sucesso",
    "organization": { ... },
    "redirect": "/login"
}
```

**Efeitos colaterais**:
- Cria roles padrão: `super_admin`, `admin`, `operator`
- Cria permissões padrão (18 permissões listadas em `SetupWizardController`)
- Associa permissões às roles
- Cria categorias de despesa padrão: Pessoal, Material, Serviços, Equipamentos, Outros
- Redireciona para `/login`

## Roles e Permissões Criadas

### Roles

| Role | Permissões |
|------|------------|
| `super_admin` | Todas (18) |
| `admin` | Todas exceto manage_users, manage_roles, manage_backups, manage_integrity, manage_maintenance, access_admin_panel |
| `operator` | view_dashboard, view_reports, manage_suppliers, manage_health_plans, manage_payables, manage_pre_launches, manage_conciliation |

### Permissões

```
view_dashboard
view_reports
manage_suppliers
manage_health_plans
manage_bank_accounts
manage_expense_categories
manage_nfes
manage_payables
manage_pre_launches
manage_conciliation
manage_cronograma
manage_audit_trail
manage_users
manage_roles
manage_backups
manage_integrity
manage_maintenance
access_admin_panel
```

## Categorias de Despesa Criadas

| Nome | Descrição | Cor |
|------|-----------|-----|
| Pessoal | Despesas com pessoal | #3B82F6 |
| Material | Material de consumo | #10B981 |
| Serviços | Serviços de terceiros | #F59E0B |
| Equipamentos | Equipamentos e manutenção | #EF4444 |
| Outros | Outras despesas | #6B7280 |

## Feature Flag

O wizard é controlado pela flag `setup_wizard`:

```env
FEATURE_SETUP_WIZARD=true
```

Com `false`, o acesso ao wizard é bloqueado (redireciona para login).

## API Reference

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/setup/status` | Retorna status de configuração |
| POST | `/api/setup/organization` | Cria organização inicial |
| POST | `/api/setup/admin` | Cria admin master |
| POST | `/api/setup/complete` | Finaliza configuração |

## Fluxo Completo

```
Usuário acessa http://localhost:9080
    ↓
Backend verifica: Organization::count() > 0?
    ↓ não
Redireciona para /setup-wizard
    ↓
Step 1: Preenche dados da instituição
    ↓ Submit
POST /api/setup/organization
    ↓
Step 2: Preenche dados do admin
    ↓ Submit
POST /api/setup/admin
    ↓
Step 3: Confirma
    ↓ Submit
POST /api/setup/complete
    ↓
Redireciona para /login
    ↓
Login com credenciais do admin
    ↓
Acessa dashboard
```

## Recuperando de Falhas

Se o wizard falhar no meio:

1. Acesse `/api/setup/status`
2. Se `is_configured=false`, o wizard pode ser reexecutado
3. Se `is_configured=true` mas a configuração está incompleta:
   - Acesse o banco e limpe a tabela `organizations` (reset)
   - Execute `php artisan migrate:fresh` (limpa tudo)
   - Acesse o wizard novamente