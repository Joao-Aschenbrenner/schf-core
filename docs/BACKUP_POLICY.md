# BACKUP_POLICY.md

# Política de Backup - SCHF Core

**Versão:** 1.0
**Data:** 2026-06-19
**Responsável:** Equipe de Infraestrutura

---

## 1. Visão Geral

Esta política define as regras e procedimentos para backup dos dados do sistema SCHF Core, garantindo a integridade, disponibilidade e recuperabilidade dos dados em caso de falhas, desastres ou erros operacionais.

---

## 2. Tipos de Backup

### 2.1 Backup Completo (Full)
- **Frequência:** Semanal (Domingos às 03:00)
- **Conteúdo:** Banco de dados completo + arquivos de upload + storage + configurações
- **Retenção:** 12 semanas (últimos 12 domingos)
- **Criptografia:** AES-256 obrigatória
 **Formato:** ZIP criptografado (AES-256)

### 2.2 Backup de Banco de Dados (Database)
- **Frequência:** Diária (02:00)
- **Conteúdo:** Dump completo do banco MySQL (structure + data)
- **Retenção:** 30 dias (últimos 30 backups diários)
- **Criptografia:** AES-256 obrigatória
- **Formato:** SQL dump comprimido + ZIP criptografado

### 2.3 Backup de Arquivos (Files)
- **Frequência:** Sob demanda / Semanal
- **Conteúdo:** Uploads, storage, arquivos de configuração
- **Retenção:** 12 semanas
- **Criptografia:** AES-256 opcional
- **Formato:** ZIP (criptografado se solicitado)

---

## 3. Agendamento (Laravel Scheduler)

```php
// config/console.php ou App\Console\Kernel.php

protected function schedule(Schedule $schedule)
{
    // Backup de banco - diário às 02:00
    $schedule->command('backup:create database --password={$password}')
        ->dailyAt('02:00')
        ->withoutOverlapping()
        ->runInBackground()
        ->onFailure(function () {
            Notification::send($admins, new BackupFailedNotification('database'));
        });

    // Backup completo - domingos às 03:00
    $schedule->command('backup:create full --password={$password}')
        ->weeklyOn(0, '03:00') // Domingo
        ->withoutOverlapping()
        ->runInBackground()
        ->onFailure(function () {
            Notification::send($admins, new BackupFailedNotification('full'));
        });

    // Backup de arquivos - semanal (sábados 04:00)
    $schedule->command('backup:create files --password={$password}')
        ->weeklyOn(6, '04:00') // Sábado
        ->withoutOverlapping()
        ->runInBackground()
        ->onFailure(function () {
            Notification::send($admins, new BackupFailedNotification('files'));
        });

    // Limpeza de backups antigos - diária às 04:30
    $schedule->command('backup:cleanup')
        ->dailyAt('04:30')
        ->withoutOverlapping();
}
```

---

## 4. Retenção e Limpeza

| Tipo | Frequência | Retenção | Critério de Exclusão |
|------|------------|----------|---------------------|
| Database (diário) | Diário | 30 dias | Manter últimos 30 |
| Full (semanal) | Semanal | 12 semanas | Manter últimos 12 domingos |
| Mensal (1º dia) | Mensal | 12 meses | Manter últimos 12 (dia 1) |
| Files | Semanal | 12 semanas | Conforme política |

**Regra de Limpeza Automática:**
- Executada diariamente às 04:30
- Remove backups excedentes conforme política acima
- Registra logs de exclusão para auditoria
- Não remove backups marcados como "preservar" (manual)

---

## 5. Criptografia e Segurança

### 5.1 Algoritmo
- **Algoritmo:** AES-256-CBC
- **Chave:** Derivada via PBKDF2 (SHA-256, 100.000 iterações) da senha do admin
- **IV:** Aleatório (16 bytes) gerado por backup
- **Armazenamento:** IV prefixado no arquivo criptografado

### 5.2 Gestão de Senhas
- Senha definida pelo admin no momento do backup
- Hash armazenado (bcrypt, cost 12) - nunca a senha em claro
- Senha necessária para: download, restauração, exportação
- Senha NÃO recuperável - se perdida, backup inutilizável

### 5.3 Integridade
- Checksum SHA-256 calculado e armazenado no registro do backup
- Verificação automática na validação e antes da restauração
- Falha de integridade bloqueia restauração e alerta admin

---

## 6. Armazenamento

### 6.1 Local Primário
- **Path:** `storage/app/backups/`
- **Disco:** Local (configurável para S3, FTP, etc.)
- **Permissões:** 750 (apenas usuário da aplicação)

### 6.2 Externo (HD/SSD USB)
- Suporte a mídia removível
- Cópia validada por checksum SHA-256
- Ejeção segura após cópia

### 6.3 Fora do Local (Off-site)
- **Recomendado:** Réplica em região geográfica diferente
- **Frequência:** Semanal (cópias dos backups completos)
- **Mecanismo:** S3 compatible / rsync / FTP seguro

---

## 7. Restauração (Restore)

### 6.1 Processo
1. Selecionar backup na interface
2. Informar senha de criptografia
3. Sistema valida senha + integridade (checksum)
4. Cria snapshot do estado atual (backup automático pré-restore)
5. Extrai e restaura conforme tipo:
   - **Database:** `mysql < dump.sql`
   - **Files/Storage:** Extração ZIP preservando estrutura
   - **Config:** Sobrescreve `config/` + `php artisan config:clear`
7. Log de auditoria completo (quem, quando, o que, antes/depois)

### 6.2 Rollback Automático
- Se restauração falhar → rollback automático para snapshot pré-restore
- Alerta imediato para admins
- Log detalhado de erro

### 6.3 Validação Pós-Restore
- Verificação de integridade das tabelas críticas
- Testes de conectividade DB
- Health check `/api/health`

---

## 7. Monitoramento e Alertas

### 7.1 Métricas Coletadas
- Status do último backup (sucesso/falha)
- Tamanho do arquivo
- Duração do processo
- Taxa de compressão
- Espaço em disco disponível

### 6.2 Alertas Configurados
| Evento | Severidade | Canal | Destinatários |
|--------|------------|-------|---------------|
| Falha no backup | Crítica | Email + Slack | Admins + DevOps |
| Backup > 2h | Aviso | Email | DevOps |
| Espaço disco < 15% | Crítica | Email + SMS | Admins + Infra |
| Restore executado | Info | Log + Email | Auditoria |
| Senha incorreta (3x) | Aviso | Log + Email | Segurança |

---

## 7. Testes e Validação

### 7.1 Testes Automatizados (CI/CD)
- Unit: BackupService, RestoreService
- Integration: Criação/Restauração de cada tipo
- E2E: Fluxo completo via API

### 7.2 Testes de Restore (Mensal)
- Restore de backup completo em ambiente de staging
- Validação de integridade dos dados
- Tempo de restore < 30 min (DB) / 60 min (Full)

### 7.3 Drill de Desastre (Trimestral)
- Simulação de perda total
- Restore completo em ambiente isolado
- RTO < 4h, RPO < 24h

---

## 8. Conformidade e Auditoria

### 8.1 LGPD / Lei de Proteção de Dados
- Backup criptografado = dado protegido
- Retenção alinhada com política de retenção de dados
- Direito ao esquecimento: exclusão segura de backups

### 8.2 Trilha de Auditoria
- Todos os eventos de backup/restore logados
- Imutável (append-only via Spatie ActivityLog)
- Rastreabilidade: quem, quando, o que, antes/depois

---

## 9. Responsabilidades

| Papel | Responsabilidade |
|-------|------------------|
| **DBA** | Execução, monitoramento, restore, tuning |
| **DevOps** | Infraestrutura, storage, monitoramento, alertas |
| **Segurança** | Gestão de chaves, políticas de criptografia |
| **Auditoria** | Validação de integridade, conformidade LGPD |
| **Admin Sistema** | Gestão de usuários, permissões, senhas de backup |

---

## 10. Revisão e Atualização

| Versão | Data | Autor | Alterações |
|--------|------|-------|------------|
| 1.0 | 2026-06-19 | Equipe Infra | Versão inicial |

**Próxima Revisão:** 2026-09-19 (trimestral)

---

**Aprovação:**

| Papel | Nome | Assinatura | Data |
|-------|------|------------|------|
| Tech Lead | _________________ | _________ | 2026-06-19 |
| DBA | _________________ | _________ | 2026-06-19 |
| Security Officer | _________________ | _________ | 2026-06-19 |
| DevOps Lead | _________________ | _________ | 2026-06-19 |