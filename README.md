# SCHF Core â€” Sistema de Controle Hospitalar e Financeiro

Plataforma genÃ©rica para gestÃ£o financeira e operacional hospitalar.

## VisÃ£o Geral

SCHF Core Ã© uma plataforma open-source para controle hospitalar e financeiro, projetada para ser implantada em qualquer instituiÃ§Ã£o de saÃºde. O sistema opera em duas camadas:

- **Operacional (2026+)** â€” Dados ativos da instituiÃ§Ã£o
- **HistÃ³rico (Legado, opcional)** â€” Dados migrados de sistemas antigos (via mÃ³dulo privado)

## Funcionalidades Principais

### Financeiro
- Contas a pagar / receber
- Notas fiscais (NFe)
- DDA (DÃ©bito Direto Autorizado)
- ConciliaÃ§Ã£o bancÃ¡ria (OFX)
- Investimentos bancÃ¡rios
- ProvisÃµes e baixas
- Caixa interno

### Operacional
- Fornecedores e convÃªnios
- Contas bancÃ¡rias
- Cronograma de pagamentos
- PrÃ©-lanÃ§amentos
- RelatÃ³rios gerenciais
- Auditoria completa (audit trail)

### AdministraÃ§Ã£o (Painel Master)
- Dashboard com mÃ©tricas do sistema
- Gerenciamento de usuÃ¡rios e roles
- Logs de auditoria
- Backups automÃ¡ticos e restore
- VerificaÃ§Ã£o de integridade
- Infraestrutura (containers, filas)
- AtualizaÃ§Ãµes via interface
- ManutenÃ§Ã£o (cache, sessÃµes, logs)

## Requisitos

- Docker 24+ / Docker Compose v2+
- 4 GB RAM mÃ­nimo (8 GB recomendado)
- 10 GB disco livre
- Portas: 9080 (HTTP), 13306 (MySQL), 6379 (Redis)

## InstalaÃ§Ã£o RÃ¡pida

```bash
# 1. Clone o repositÃ³rio
git clone https://github.com/ORG/schf-core.git
cd schf-core

# 2. Configure variÃ¡veis de ambiente
cp .env.example .env
# Edite .env com suas senhas

# 3. Suba os containers
docker compose up -d

# 4. Execute migrations e seeders
docker exec schf-backend php artisan migrate --force
docker exec schf-backend php artisan db:seed --force

# 5. Acesse http://localhost:9080
# SerÃ¡ redirecionado para /setup-wizard na primeira vez
```

## InstalaÃ§Ã£o via Script (Windows)

```powershell
# Na raiz do projeto
.\installer\install.ps1 -InstallPath "C:\SCHF"
```

## ConfiguraÃ§Ã£o Inicial (Wizard)

Na primeira execuÃ§Ã£o, o sistema detecta que nÃ£o hÃ¡ organizaÃ§Ã£o cadastrada e redireciona para o wizard:

1. **InstituiÃ§Ã£o** â€” Nome, CNPJ, cidade, estado, contato
2. **Administrador Master** â€” Nome, e-mail, senha (criado com `is_master=true`)
3. **ConcluÃ­do** â€” Roles, permissÃµes e categorias padrÃ£o criados automaticamente

## AtualizaÃ§Ãµes

O painel admin (`/admin/updates`) permite:
- Verificar nova versÃ£o no GitHub Releases
- Executar atualizaÃ§Ã£o (docker pull + migrate + health check + rollback automÃ¡tico)
- Visualizar changelog
- Rollback para versÃ£o anterior

## MÃ³dulo Legado (Opcional)

Para instituiÃ§Ãµes migrando de sistemas Firebird legados:

```bash
# No repositÃ³rio privado schf-migration
.\install-migration.ps1 -FirebirdPath "C:\caminho/para/banco\db\banco_legado.fdb"
```

Isso habilita `FEATURE_LEGACY_MODULE=true` e adiciona o menu "Consultas Antigas".

## Estrutura do Projeto

```
schf-core/
â”œâ”€â”€ backend/              # Laravel 11 API
â”‚   â”œâ”€â”€ app/
â”‚   â”‚   â”œâ”€â”€ Http/Controllers/
â”‚   â”‚   â”‚   â”œâ”€â”€ Admin/    # Painel master
â”‚   â”‚   â”‚   â””â”€â”€ Operacional/  # 2026+
â”‚   â”‚   â”œâ”€â”€ Models/
â”‚   â”‚   â”œâ”€â”€ Services/
â”‚   â”‚   â””â”€â”€ Policies/
â”‚   â”œâ”€â”€ database/
â”‚   â”‚   â”œâ”€â”€ migrations/
â”‚   â”‚   â””â”€â”€ seeders/
â”‚   â””â”€â”€ routes/api.php
â”œâ”€â”€ frontend/             # React + TypeScript + Vite
â”‚   â”œâ”€â”€ src/
â”‚   â”‚   â”œâ”€â”€ pages/
â”‚   â”‚   â”‚   â””â”€â”€ admin/    # Painel master
â”‚   â”‚   â”œâ”€â”€ components/
â”‚   â”‚   â”œâ”€â”€ services/
â”‚   â”‚   â”œâ”€â”€ stores/
â”‚   â”‚   â””â”€â”€ hooks/
â”‚   â””â”€â”€ src-tauri/        # Tauri (desktop, prioridade baixa)
â”œâ”€â”€ infra/                # Docker, Nginx, MySQL configs
â”œâ”€â”€ installer/            # Scripts de instalaÃ§Ã£o
â”œâ”€â”€ docs/                 # DocumentaÃ§Ã£o
â”œâ”€â”€ docker-compose.yml
â””â”€â”€ .github/workflows/    # CI/CD
```

## VariÃ¡veis de Ambiente Principais

| VariÃ¡vel | DescriÃ§Ã£o | PadrÃ£o |
|----------|-----------|--------|
| `APP_KEY` | Chave de criptografia Laravel | Gerar na instalaÃ§Ã£o |
| `DB_PASSWORD` | Senha do banco | `change_me_in_production` |
| `REDIS_PASSWORD` | Senha Redis | `change_me_in_production` |
| `FEATURE_LEGACY_MODULE` | Habilita mÃ³dulo histÃ³rico | `false` |
| `APP_UPDATE_REPO` | Repo GitHub para updates | `ORG/schf-core` |

## Desenvolvimento

```bash
# Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve

# Frontend
cd frontend
npm install
npm run dev

# Tauri (desktop)
cd frontend
npm run tauri dev
```

## Testes

```bash
# Backend
cd backend
./vendor/bin/pest
./vendor/bin/phpstan analyse

# Frontend
cd frontend
npm run test
npm run e2e
```

## LicenÃ§a

MIT License â€” veja [LICENSE](LICENSE) para detalhes.

## ContribuiÃ§Ã£o

1. Fork o projeto
2. Crie branch (`git checkout -b feature/nova-funcionalidade`)
3. Commit (`git commit -am 'Add nova funcionalidade'`)
4. Push (`git push origin feature/nova-funcionalidade`)
5. Abra Pull Request

## Suporte

- Issues: [GitHub Issues](https://github.com/ORG/schf-core/issues)
- DocumentaÃ§Ã£o: [docs/](docs/)
- Changelog: [CHANGELOG.md](CHANGELOG.md)

