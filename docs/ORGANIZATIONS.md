# Multi-Organization Architecture

O SCHF Core suporta múltiplas organizações (instituições hospitalares) em uma única instalação, permitindo que diferentes entidades operem de forma isolada.

## Conceitos

### Organização

Uma **Organização** representa uma instituição de saúde (hospital, clínica, laboratório). Cada organização possui:

- Identificação: nome, CNPJ, endereço
- Configurações: e-mail, telefone, cidade, estado
- Status: `is_active` (ativa/inativa), `is_primary` (organização primária)
- Configurações customizadas: `settings` (JSON flexível)
- Relacionamento com usuários e dados financeiros

### Usuário por Organização

Cada usuário pertence a **uma organização** específica:

```php
$user->organization_id  // FK para organizations
$user->organization     // Relação Eloquent
```

O `OrganizationPolicy` garante que usuários só acessem dados da própria organização.

## Hierarquia

```
SCHF Core
├── Organization A (primary)
│   ├── User A1 (master)
│   ├── User A2 (admin)
│   └── User A3 (operator)
├── Organization B
│   ├── User B1 (master)
│   └── User B2 (operator)
└── Organization C (inactive)
    └── User C1 (master)
```

## Modelo de Dados

### Organization

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | bigint | PK |
| `name` | string | Nome da instituição |
| `cnpj` | string(18) | CNPJ (único) |
| `city` | string(100) | Cidade |
| `state` | string(2) | UF |
| `email` | string | E-mail de contato |
| `phone` | string(20) | Telefone |
| `address` | text | Endereço completo |
| `is_primary` | boolean | É a organização primária |
| `is_active` | boolean | Está ativa |
| `settings` | JSON | Configurações customizadas |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### User → Organization

```php
// User.php
public function organization()
{
    return $this->belongsTo(Organization::class);
}
```

## API de Organizações

### Listar

```
GET /api/organizations
```

Retorna todas as organizações (filtrado por permissão).

### Criar

```
POST /api/organizations
```

Requer: `access-admin` gate ou role `super_admin`

### Ver

```
GET /api/organizations/{id}
```

### Atualizar

```
PUT /api/organizations/{id}
```

### Deletar

```
DELETE /api/organizations/{id}
```

Requer: `delete` no OrganizationPolicy

### Ativar/Desativar

```
POST /api/organizations/{id}/activate
POST /api/organizations/{id}/deactivate
```

### Definir como Primária

```
POST /api/organizations/{id}/set-primary
```

Apenas uma organização pode ser primária. A anterior perde o status.

## Events

| Evento | Disparo |
|--------|---------|
| `OrganizationCreated` | Após criar organização via API |
| `OrganizationActivated` | Após ativar ou desativar |

## Policies

O `OrganizationPolicy` define permissões granulares:

| Ação | Permissão |
|------|----------|
| `viewAny` | Qualquer usuário autenticado |
| `view` | Dono ou `super_admin` |
| `create` | `access-admin` gate |
| `update` | Dono ou `super_admin` |
| `delete` | Apenas `super_admin` ou `is_master` |
| `activate` | `super_admin` ou `is_master` |
| `deactivate` | `super_admin` ou `is_master` |
| `setPrimary` | Apenas `is_master` |

## Setup Wizard e Multi-Org

O Setup Wizard cria a **primeira organização**:

1. **Criação da Organização** → `is_primary=true`, `is_active=true`
2. **Criação do Admin** → `is_master=true`, `is_system_admin=true`, `organization_id` aponta para org criada
3. **Admin recebe role** `super_admin`

Organizações adicionais são criadas via API (`POST /api/organizations`) por usuários com permissão.

## Configuração

### Feature Flag

O suporte a multi-org é controlado pela flag:

```env
FEATURE_MULTI_ORGANIZATION=true
```

Com `false`, o sistema opera em modo single-tenant.

### Middleware de Contexto

O `OrganizationContextMiddleware` (se habilitado) define a organização ativa para cada request com base no token Sanctum do usuário.

## Dados por Organização

Todos os modelos que armazenam dados institucionais possuem `organization_id`:

- `ExpenseCategory`
- `Supplier`
- `HealthPlan`
- `BankAccount`
- `Nfe`
- `Payable`
- `PreLaunch`
- `Dda`
- etc.

Isso garante isolamento completo de dados entre organizações.

## Migrações

### Criar organização manualmente

```php
$org = Organization::create([
    'name' => 'Hospital Exemplo',
    'cnpj' => '12.345.678/0001-90',
    'city' => 'São Paulo',
    'state' => 'SP',
    'is_primary' => false,
    'is_active' => true,
]);
```

### Criar usuário para organização

```php
$user = User::create([
    'name' => 'João Silva',
    'email' => 'joao@hospital-exemplo.com',
    'password' => Hash::make('senha123'),
    'organization_id' => $org->id,
    'is_master' => false,
    'is_active' => true,
]);
$user->assignRole('operator');
```

## Limitações

- Autenticação Sanctum não é nativamente multi-org (token Sanctum não inclui `organization_id` naclaims)
- Queries que listam dados devem sempre filtrar por `organization_id`
- Relatórios agregados entre organizações requerem implementação específica