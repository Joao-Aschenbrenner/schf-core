# Guia de InstalaÃ§Ã£o â€” SCHF

**Projeto:** Sistema Financeiro â€” SCHF  
**VersÃ£o:** 0.2.0  
**Data:** 14 de Junho de 2026

---

## PrÃ©-requisitos

| Software | VersÃ£o MÃ­nima | Download |
|----------|---------------|----------|
| Docker Desktop | 4.20+ | https://www.docker.com/products/docker-desktop |
| Node.js | 20+ | https://nodejs.org/ |
| npm | 10+ | IncluÃ­do com Node.js |
| Git | 2.40+ | https://git-scm.com/ |

### Opcional (para build desktop)

| Software | VersÃ£o MÃ­nima | Download |
|----------|---------------|----------|
| Rust | 1.75+ | https://rustup.rs/ |
| Visual Studio Build Tools | â€” | https://visualstudio.microsoft.com/visual-cpp-build-tools/ |

---

## Passo 1 â€” Clone do RepositÃ³rio

```bash
git clone <url-do-repositorio> financeiro-schf
cd financeiro-schf
```

---

## Passo 2 â€” ConfiguraÃ§Ã£o de VariÃ¡veis de Ambiente

```bash
# Copiar o arquivo de exemplo
cp .env.example .env

# Editar o arquivo .env (opcional â€” valores padrÃ£o funcionam)
# Assegurar que APP_KEY seja gerada no prÃ³ximo passo
```

### VariÃ¡veis Importantes

| VariÃ¡vel | Valor PadrÃ£o | DescriÃ§Ã£o |
|----------|--------------|-----------|
| `APP_ENV` | `local` | Ambiente (local, production) |
| `APP_DEBUG` | `true` | Modo debug (false em produÃ§Ã£o) |
| `DB_DATABASE` | `schf` | Nome do banco de dados |
| `DB_USERNAME` | `schf` | UsuÃ¡rio do banco |
| `DB_PASSWORD` | `schf_secret` | Senha do banco |
| `DB_ROOT_PASSWORD` | `root_secret` | Senha root do MySQL |

---

## Passo 3 â€” Backend (Docker)

### Iniciar os containers

```bash
docker compose up -d
```

Isso irÃ¡ iniciar 7 serviÃ§os:
- `schf-backend` (Laravel â€” porta 9000)
- `schf-frontend` (Vite â€” porta 1420)
- `schf-nginx` (Nginx â€” porta 9080)
- `schf-mysql` (MySQL 8.0 â€” porta 13306)
- `schf-redis` (Redis 7 â€” porta 16379)
- `schf-queue` (Queue Worker)
- `schf-pma` (phpMyAdmin â€” porta 9081)

### Aguardar os serviÃ§os ficarem prontos

```bash
# Verificar status
docker compose ps

# Verificar logs do backend
docker compose logs -f backend
```

### Gerar chave do Laravel

```bash
docker compose exec backend php artisan key:generate
```

### Executar migraÃ§Ãµes

```bash
docker compose exec backend php artisan migrate
```

### Rodar seeders (dados iniciais)

```bash
docker compose exec backend php artisan db:seed
```

### Verificar o backend

```bash
curl http://localhost:9080/api/health
# Deve retornar: {"status":"ok"}
```

---

## Passo 4 â€” Frontend (Desenvolvimento Local)

### Instalar dependÃªncias

```bash
cd frontend
npm install
```

### Iniciar servidor de desenvolvimento

```bash
npm run dev
```

O frontend estarÃ¡ disponÃ­vel em: **http://localhost:1420**

### Verificar o frontend

Acesse http://localhost:1420 no navegador. A tela de login deve aparecer com o indicador de status do backend.

---

## Passo 5 â€” Build Desktop (Tauri)

### PrÃ©-requisitos para Tauri

```bash
# Instalar Rust (Windows)
winget install Rustlang.Rustup

# Reiniciar o terminal apÃ³s instalaÃ§Ã£o

# Verificar instalaÃ§Ã£o
rustc --version
cargo --version
```

### Build do aplicativo desktop

```bash
cd frontend
npm install
npm run tauri build
```

### Artefatos gerados

| Artefato | LocalizaÃ§Ã£o | Tamanho |
|----------|-------------|---------|
| ExecutÃ¡vel (.exe) | `frontend/src-tauri/target/release/` | ~6.08 MB |
| Instalador NSIS | `frontend/src-tauri/target/release/bundle/nsis/` | ~2.2 MB |
| Instalador MSI | `frontend/src-tauri/target/release/bundle/msi/` | ~3.07 MB |

### Instalar o aplicativo

1. Navegue atÃ© a pasta `frontend/src-tauri/target/release/bundle/`
2. Execute o instalador NSIS (`.exe`) ou MSI (`.msi`)
3. Siga o assistente de instalaÃ§Ã£o
4. O atalho serÃ¡ criado na Ã¡rea de trabalho e menu iniciar

---

## Passo 6 â€” Credenciais PadrÃ£o

| Campo | Valor |
|-------|-------|
| Email | `admin@schf.com` |
| Senha | `password` |

> **Importante:** Altere a senha apÃ³s o primeiro login em ambiente de produÃ§Ã£o.

---

## Passo 7 â€” VerificaÃ§Ã£o Final

### Checklist de InstalaÃ§Ã£o

- [ ] Docker Desktop rodando
- [ ] Containers todos Up (verificar com `docker compose ps`)
- [ ] Backend respondendo em http://localhost:9080/api/health
- [ ] Frontend acessÃ­vel em http://localhost:1420
- [ ] phpMyAdmin acessÃ­vel em http://localhost:9081
- [ ] Login funcional com credenciais padrÃ£o
- [ ] Dashboard carregando dados

### Portas Utilizadas

| ServiÃ§o | Porta | URL |
|---------|-------|-----|
| Nginx (API + Frontend) | 9080 | http://localhost:9080 |
| Frontend (Dev) | 1420 | http://localhost:1420 |
| MySQL | 13306 | localhost:13306 |
| Redis | 16379 | localhost:16379 |
| Backend (PHP-FPM) | 9000 | localhost:9000 |
| phpMyAdmin | 9081 | http://localhost:9081 |

---

## SoluÃ§Ã£o de Problemas

### Backend nÃ£o inicia

```bash
# Verificar logs
docker compose logs backend

# Rebuild do container
docker compose down
docker compose build --no-cache backend
docker compose up -d
```

### Erro de banco de dados

```bash
# Verificar se MySQL estÃ¡ pronto
docker compose exec mysql mysql -u root -proot_secret -e "SHOW DATABASES;"

# Recriar banco
docker compose exec backend php artisan migrate:fresh --seed
```

### Porta jÃ¡ em uso

```bash
# Verificar processos na porta
netstat -ano | findstr :9080

# Matar processo (substitua <PID>)
taskkill /PID <PID> /F
```

### Frontend nÃ£o conecta ao backend

1. Verifique se o backend estÃ¡ rodando: `docker compose ps`
2. Verifique a configuraÃ§Ã£o de API no frontend
3. Verifique se o Nginx estÃ¡ roteando corretamente

---

## Comandos Ãšteis

```bash
# Status dos containers
docker compose ps

# Logs em tempo real
docker compose logs -f

# Parar todos os serviÃ§os
docker compose down

# Rebuild completo
docker compose down
docker compose build --no-cache
docker compose up -d

# Acessar shell do backend
docker compose exec backend bash

# Executar testes backend
docker compose exec backend pest

# Executar testes frontend
cd frontend && npm run test:run

# Executar testes E2E
cd frontend && npx playwright test
```

---

*Guia gerado por: opencode â€” 14 de Junho de 2026*


