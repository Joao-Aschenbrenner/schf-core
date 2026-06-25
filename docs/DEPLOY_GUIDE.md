# Guia de ImplantaÃ§Ã£o em ProduÃ§Ã£o â€” SCHF

**Projeto:** Sistema Financeiro â€” SCHF  
**VersÃ£o:** 0.2.0  
**Data:** 14 de Junho de 2026

---

## SumÃ¡rio

1. [PrÃ©-requisitos](#1-prÃ©-requisitos)
2. [ConfiguraÃ§Ã£o do Ambiente](#2-configuraÃ§Ã£o-do-ambiente)
3. [Docker Compose â€” ProduÃ§Ã£o](#3-docker-compose--produÃ§Ã£o)
4. [VariÃ¡veis de Ambiente](#4-variÃ¡veis-de-ambiente)
5. [SSL/TLS](#5-ssltls)
6. [Backup do Banco de Dados](#6-banco-de-dados)
7. [Monitoramento](#7-monitoramento)
8. [ManutenÃ§Ã£o](#8-manutenÃ§Ã£o)

---

## 1. PrÃ©-requisitos

### Servidor

| Requisito | EspecificaÃ§Ã£o |
|-----------|---------------|
| Sistema Operacional | Linux (Ubuntu 22.04+), Windows Server 2019+ |
| CPU | 2+ cores |
| RAM | 4+ GB |
| Disco | 50+ GB SSD |
| Rede | Acesso Ã  internet (para dependÃªncias) |

### Software

| Software | VersÃ£o | InstalaÃ§Ã£o |
|----------|--------|------------|
| Docker | 24+ | https://docs.docker.com/engine/install/ |
| Docker Compose | 2.20+ | https://docs.docker.com/compose/install/ |
| Git | 2.40+ | https://git-scm.com/ |

---

## 2. ConfiguraÃ§Ã£o do Ambiente

### Clonar o repositÃ³rio

```bash
git clone <url-do-repositorio> /opt/schf-core
cd /opt/schf-core
```

### Criar arquivo .env de produÃ§Ã£o

```bash
cp .env.example .env
```

### Editar .env

```bash
nano .env
```

ConfiguraÃ§Ãµes essenciais para produÃ§Ã£o:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://financeiro.hospital.local

DB_DATABASE=schf
DB_USERNAME=schf
DB_PASSWORD=<SENHA_FORTE_AQUI>
DB_ROOT_PASSWORD=<SENHA_ROOT_FORTE_AQUI>

REDIS_PASSWORD=<SENHA_REDIS_AQUI>

SANCTUM_STATEFUL_DOMAINS=financeiro.hospital.local
SESSION_DOMAIN=.schf.com.br

FRONTEND_URL=https://financeiro.hospital.local
```

---

## 3. Docker Compose â€” ProduÃ§Ã£o

### Criar docker-compose.prod.yml

```yaml
services:
  backend:
    build:
      context: ./backend
      dockerfile: Dockerfile
    container_name: schf-backend
    restart: always
    working_dir: /var/www/html
    volumes:
      - ./backend:/var/www/html
      - ./storage:/var/www/html/storage
    ports:
      - "127.0.0.1:9000:9000"
    depends_on:
      - mysql
      - redis
    networks:
      - schf-network
    environment:
      - DB_HOST=mysql
      - DB_DATABASE=${DB_DATABASE}
      - DB_USERNAME=${DB_USERNAME}
      - DB_PASSWORD=${DB_PASSWORD}
      - REDIS_HOST=redis
      - REDIS_PASSWORD=${REDIS_PASSWORD}
      - CACHE_DRIVER=redis
      - QUEUE_CONNECTION=redis
    healthcheck:
      test: ["CMD", "php", "artisan", "tinker", "--execute=echo 'ok'"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

  nginx:
    image: nginx:alpine
    container_name: schf-nginx
    restart: always
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./infra/nginx/nginx.conf:/etc/nginx/conf.d/default.conf
      - ./infra/nginx/ssl:/etc/nginx/ssl
      - ./backend/public:/var/www/html/public
    depends_on:
      - backend
      - frontend
    networks:
      - schf-network

  mysql:
    image: mysql:8.0
    container_name: schf-mysql
    restart: always
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
    volumes:
      - mysql-data:/var/lib/mysql
      - ./infra/mysql/my.cnf:/etc/mysql/conf.d/my.cnf
      - ./backups:/backups
    networks:
      - schf-network
    command: --default-authentication-plugin=mysql_native_password

  redis:
    image: redis:7-alpine
    container_name: schf-redis
    restart: always
    command: redis-server --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis-data:/data
    networks:
      - schf-network

  queue:
    build:
      context: ./backend
      dockerfile: Dockerfile
    container_name: schf-queue
    restart: always
    working_dir: /var/www/html
    command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
    volumes:
      - ./backend:/var/www/html
      - ./storage:/var/www/html/storage
    depends_on:
      - mysql
      - redis
    networks:
      - schf-network
    environment:
      - DB_HOST=mysql
      - DB_DATABASE=${DB_DATABASE}
      - DB_USERNAME=${DB_USERNAME}
      - DB_PASSWORD=${DB_PASSWORD}
      - REDIS_HOST=redis
      - CACHE_DRIVER=redis
      - QUEUE_CONNECTION=redis

networks:
  schf-network:
    driver: bridge

volumes:
  mysql-data:
  redis-data:
```

### Iniciar em produÃ§Ã£o

```bash
# Build e iniciar
docker compose -f docker-compose.prod.yml up -d --build

# Executar migraÃ§Ãµes
docker compose -f docker-compose.prod.yml exec backend php artisan migrate --force

# Rodar seeders (apenas na primeira vez)
docker compose -f docker-compose.prod.yml exec backend php artisan db:seed --force

# Cache de configuraÃ§Ã£o
docker compose -f docker-compose.prod.yml exec backend php artisan config:cache
docker compose -f docker-compose.prod.yml exec backend php artisan route:cache
docker compose -f docker-compose.prod.yml exec backend php artisan view:cache

# Otimizar autoloader
docker compose -f docker-compose.prod.yml exec backend composer install --optimize-autoloader --no-dev
```

---

## 4. VariÃ¡veis de Ambiente

### VariÃ¡veis ObrigatÃ³rias

| VariÃ¡vel | DescriÃ§Ã£o | Exemplo |
|----------|-----------|---------|
| `APP_ENV` | Ambiente | `production` |
| `APP_DEBUG` | Modo debug | `false` |
| `APP_URL` | URL do sistema | `https://financeiro.hospital.local` |
| `APP_KEY` | Chave de criptografia | Gerada via `php artisan key:generate` |
| `DB_DATABASE` | Nome do banco | `schf` |
| `DB_USERNAME` | UsuÃ¡rio do banco | `schf` |
| `DB_PASSWORD` | Senha do banco | Senha forte |
| `DB_ROOT_PASSWORD` | Senha root MySQL | Senha forte |
| `REDIS_PASSWORD` | Senha do Redis | Senha forte |

### VariÃ¡veis Opcionais

| VariÃ¡vel | DescriÃ§Ã£o | PadrÃ£o |
|----------|-----------|--------|
| `SANCTUM_STATEFUL_DOMAINS` | DomÃ­nios autenticados | `localhost` |
| `SESSION_DOMAIN` | DomÃ­nio da sessÃ£o | `localhost` |
| `FRONTEND_URL` | URL do frontend | `http://localhost:3000` |

---

## 5. SSL/TLS

### OpÃ§Ã£o 1 â€” Let's Encrypt (Recomendado)

```bash
# Instalar Certbot
sudo apt install certbot

# Obter certificado
sudo certbot certonly --standalone -d financeiro.hospital.local

# Certificados estarÃ£o em:
# /etc/letsencrypt/live/financeiro.hospital.local/fullchain.pem
# /etc/letsencrypt/live/financeiro.hospital.local/privkey.pem
```

### Configurar Nginx para HTTPS

```nginx
server {
    listen 80;
    server_name financeiro.hospital.local;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name financeiro.hospital.local;

    ssl_certificate /etc/nginx/ssl/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Headers de seguranÃ§a
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options DENY always;
    add_header X-Content-Type-Options nosniff always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Proxy para backend
    location /api {
        proxy_pass http://backend:9000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Frontend
    location / {
        root /var/www/html/public;
        try_files $uri $uri/ /index.html;
    }
}
```

### OpÃ§Ã£o 2 â€” Certificado Interno

```bash
# Gerar certificado auto-assinado
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/nginx/ssl/privkey.pem \
  -out /etc/nginx/ssl/fullchain.pem \
  -subj "/C=BR/ST=SP/L=Cidade/O=SCHF/CN=financeiro.hospital.local"
```

### RenovaÃ§Ã£o AutomÃ¡tica (Let's Encrypt)

```bash
# Adicionar cron job
sudo crontab -e

# Adicionar linha:
0 12 * * * /usr/bin/certbot renew --quiet && docker compose -f /opt/schf-core/docker-compose.prod.yml restart nginx
```

---

## 6. Backup do Banco de Dados

### Backup AutomÃ¡tico com Docker

```bash
# Criar script de backup
cat > /opt/schf-core/scripts/backup.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/schf-core/backups"
CONTAINER="schf-mysql"
DB_NAME="schf"
DB_USER="root"
DB_PASS="${DB_ROOT_PASSWORD}"

# Criar backup
docker exec $CONTAINER mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/backup_$DATE.sql

# Comprimir
gzip $BACKUP_DIR/backup_$DATE.sql

# Manter apenas Ãºltimos 30 backups
ls -t $BACKUP_DIR/backup_*.sql.gz | tail -n +31 | xargs rm -f 2>/dev/null

echo "Backup concluÃ­do: backup_$DATE.sql.gz"
EOF

chmod +x /opt/schf-core/scripts/backup.sh
```

### Agendar Backup DiÃ¡rio

```bash
# Adicionar cron job (todo dia Ã s 2h)
sudo crontab -e

# Adicionar linha:
0 2 * * * /opt/schf-core/scripts/backup.sh >> /var/log/schf-backup.log 2>&1
```

### Restaurar Backup

```bash
# Descompactar
gunzip backup_20260614_020000.sql.gz

# Restaurar
docker exec -i schf-mysql mysql -uroot -pSENHA_ROOT schf < backup_20260614_020000.sql
```

### Backup com phpMyAdmin

1. Acesse http://localhost:9081
2. Selecione o banco `schf`
3. Clique em **Exportar**
4. Escolha **RÃ¡pido** ou **Personalizado**
5. Clique em **Executar**

---

## 7. Monitoramento

### Health Check do Backend

```bash
# Verificar status
curl -s http://localhost:9080/api/health | jq .

# Resposta esperada:
# {
#   "status": "ok",
#   "timestamp": "2026-06-14T10:00:00Z"
# }
```

### Monitorar Containers

```bash
# Status dos containers
docker compose -f docker-compose.prod.yml ps

# Logs em tempo real
docker compose -f docker-compose.prod.yml logs -f

# Logs de um serviÃ§o especÃ­fico
docker compose -f docker-compose.prod.yml logs -f backend

# Uso de recursos
docker stats
```

### Monitoramento com Docker

```bash
# Instalar cAdvisor (monitoramento de containers)
# Adicionar ao docker-compose.prod.yml:

  cadvisor:
    image: gcr.io/cadvisor/cadvisor:latest
    container_name: schf-cadvisor
    restart: always
    ports:
      - "8082:8080"
    volumes:
      - /:/rootfs:ro
      - /var/run:/var/run:rw
      - /sys:/sys:ro
      - /var/lib/docker/:/var/lib/docker:ro
    networks:
      - schf-network
```

### Log Management

```bash
# Configurar rotaÃ§Ã£o de logs
# Criar /etc/docker/daemon.json:
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  }
}

# Reiniciar Docker
sudo systemctl restart docker
```

### Alertas

```bash
# Script de verificaÃ§Ã£o de saÃºde
cat > /opt/schf-core/scripts/health-check.sh << 'EOF'
#!/bin/bash
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/api/health)

if [ "$RESPONSE" != "200" ]; then
    echo "ALERTA: Backend nÃ£o estÃ¡ respondendo! HTTP $RESPONSE" | \
    mail -s "SCHF - Alerta" admin@schf.com.br
fi
EOF

chmod +x /opt/schf-core/scripts/health-check.sh

# Agendar a cada 5 minutos
crontab -e
*/5 * * * * /opt/schf-core/scripts/health-check.sh
```

---

## 8. ManutenÃ§Ã£o

### Atualizar o Sistema

```bash
cd /opt/schf-core

# Pull das Ãºltimas alteraÃ§Ãµes
git pull origin main

# Rebuild dos containers
docker compose -f docker-compose.prod.yml build --no-cache

# Reiniciar serviÃ§os
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml up -d

# Executar migraÃ§Ãµes (se houver)
docker compose -f docker-compose.prod.yml exec backend php artisan migrate --force

# Limpar caches
docker compose -f docker-compose.prod.yml exec backend php artisan cache:clear
docker compose -f docker-compose.prod.yml exec backend php artisan config:cache
```

### Limpeza de Logs

```bash
# Limpar logs do Docker
docker system prune -f

# Limpar logs antigos
find /var/lib/docker/containers/*/*.log -size +100M -delete
```

### ManutenÃ§Ã£o do Banco

```bash
# Otimizar tabelas
docker exec schf-mysql mysqlcheck -u root -pSENHA_ROOT --optimize schf

# Verificar integridade
docker exec schf-mysql mysqlcheck -u root -pSENHA_ROOT --check schf
```

### Escala Horizontal

Para ambientes com alta demanda, considere:

1. **Load Balancer:** Nginx ou HAProxy na frente de mÃºltiplos backends
2. **Banco de Dados:** MySQL Master-Slave replication
3. **Redis:** Redis Sentinel para alta disponibilidade
4. **Storage:** NFS ou S3 para arquivos compartilhados

---

## Comandos de ProduÃ§Ã£o

```bash
# Iniciar tudo
docker compose -f docker-compose.prod.yml up -d --build

# Parar tudo
docker compose -f docker-compose.prod.yml down

# Status
docker compose -f docker-compose.prod.yml ps

# Logs
docker compose -f docker-compose.prod.yml logs -f

# Backup
/opt/schf-core/scripts/backup.sh

# Health check
curl -s http://localhost:9080/api/health

# Shell do backend
docker compose -f docker-compose.prod.yml exec backend bash

# Shell do MySQL
docker exec -it schf-mysql mysql -uroot -p
```

---

## Checklist de ProduÃ§Ã£o

- [ ] Servidor com Docker instalado
- [ ] RepositÃ³rio clonado em `/opt/schf-core`
- [ ] Arquivo `.env` configurado com senhas fortes
- [ ] Containers Buildados e rodando
- [ ] MigraÃ§Ãµes executadas
- [ ] SSL/TLS configurado e funcionando
- [ ] Backup automatizado configurado
- [ ] Monitoramento configurado
- [ ] Health check respondendo 200
- [ ] phpMyAdmin desabilitado ou protegido
- [ ] Logs configurados com rotaÃ§Ã£o
- [ ] Firewall configurado (apenas portas 80, 443)
- [ ] DNS apontando para o servidor
- [ ] Teste de login com credenciais reais

---

*Guia gerado por: opencode â€” 14 de Junho de 2026*


