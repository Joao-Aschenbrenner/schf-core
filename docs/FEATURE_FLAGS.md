# Feature Flags

O SCHF Core utiliza um sistema de feature flags para controle granular de funcionalidades. Flags são configuradas via variáveis de ambiente e acessadas programaticamente.

## Flags Disponíveis

| Flag | Variável de Ambiente | Padrão | Descrição |
|------|---------------------|--------|-----------|
| `legacy_module` | `FEATURE_LEGACY_MODULE` | `false` | Habilita módulo de consultas legadas (Firebird) |
| `multi_organization` | `FEATURE_MULTI_ORGANIZATION` | `true` | Habilita suporte a múltiplas organizações |
| `setup_wizard` | `FEATURE_SETUP_WIZARD` | `true` | Habilita o wizard de configuração inicial |
| `auto_updates` | `FEATURE_AUTO_UPDATES` | `false` | Habilita verificação e aplicação automática de updates |
| `desktop_app` | `FEATURE_DESKTOP_APP` | `false` | Habilita recursos específicos do app desktop (Tauri) |
| `developer_mode` | `FEATURE_DEVELOPER_MODE` | `false` | Habilita ferramentas de desenvolvimento e debug |

## Configuração

### Via .env

```env
FEATURE_LEGACY_MODULE=false
FEATURE_MULTI_ORGANIZATION=true
FEATURE_SETUP_WIZARD=true
FEATURE_AUTO_UPDATES=false
FEATURE_DESKTOP_APP=false
FEATURE_DEVELOPER_MODE=false
```

### Via Install Script

O script `installer/install.ps1` configura automaticamente todas as flags com os valores padrão.

## Acesso Programático

### Via Helper Global

```php
// Verificar flag específica
if (feature('multi_organization')) {
    // Código para multi-org
}

// Verificar flag com método dedicado
if (feature()->legacyModule()) {
    // Código legacy
}

// Listar todas as flags
$flags = feature()->all();
// ['legacy_module' => false, 'multi_organization' => true, ...]
```

### Via Service

```php
use App\Services\FeatureFlagService;

public function __construct(FeatureFlagService $flags)
{
    if ($flags->multiOrganization()) {
        // Código para multi-org
    }

    if ($flags->desktopApp()) {
        // Código desktop
    }
}
```

### No Frontend (React)

```typescript
import { feature } from '@/services/featureApi'

// Verificar flag
const flags = await feature.getAll()
if (flags.multi_organization) {
    // Mostrar selector de organização
}
```

## Comportamento por Flag

### legacy_module

- **false**: Oculta menu "Consultas Antigas" e desabilita conector Firebird
- **true**: Habilita conector Firebird e menu de consultas legadas

### multi_organization

- **false**: Sistema opera em modo single-tenant (uma organização)
- **true**: Sistema suporta múltiplas organizações com selector no header

### setup_wizard

- **false**: Wizard de primeira configuração é desabilitado
- **true**: Usuário não autenticado é redirecionado para `/setup-wizard`

### auto_updates

- **false**: Painel de updates mostra "Recurso desabilitado"
- **true**: Permite verificar, baixar e aplicar updates via GitHub Releases

### desktop_app

- **false**: App desktop usa apenas UI web
- **true**: Habilita recursos Tauri (atalhos, notificações nativas, etc.)

### developer_mode

- **false**: Rotas de debug desabilitadas, logs mínimos
- **true**: Habilita `/admin/system/info`, logs detalhados, cache desabilitado

## Events

Quando uma flag é alterada, os seguintes eventos são disparados:

- `FeatureFlagChanged` - Quando qualquer flag é alterada (via admin panel futuro)

## Validação

O `AppServiceProvider` valida flags ao iniciar:

```php
// Skip em console (migrations, seeders, etc.)
if ($this->app->runningInConsole()) {
    return;
}

// Flags disponíveis globalmente após conexão com banco
config(['app.is_configured' => Organization::count() > 0]);
```

## Segurança

- Flags são configuradas via `.env` (não exposto em código)
- Acesso via admin requer role `super_admin` ou `is_master=true`
- Flags não afetam rotas de autenticação pública (setup wizard, login)