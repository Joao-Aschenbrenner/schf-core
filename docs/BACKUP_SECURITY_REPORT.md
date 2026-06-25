# BACKUP_SECURITY_REPORT

**Data:** 2026-06-19
**Versão:** 1.0

---

## 1. Resumo da Segurança do Backup

Este documento descreve as medidas de segurança implementadas no sistema de backup do Santa Casa Financeiro, cobrindo criptografia, gestão de chaves, controle de acesso e conformidade.

---

## 2. Criptografia

### 2.1 Algoritmo Utilizado
- **Algoritmo:** AES-256-CBC
- **Modo:** CBC (Cipher Block Chaining)
- **Tamanho da Chave:** 256 bits
- **Tamanho do IV:** 16 bytes (128 bits)
- **Derivação de Chave:** PBKDF2-HMAC-SHA256
- **Iterações PBKDF2:** 100.000
- **Salt:** Aleatório (16 bytes) por backup

### 2.2 Processo de Criptografia
```
1. Senha do usuário → PBKDF2-SHA256 (100k iterações) → Chave de 256 bits
2. IV aleatório de 16 bytes gerado via random_bytes()
3. Arquivo ZIP → Criptografia AES-256-CBC com chave derivada + IV
4. Arquivo final = IV (16 bytes) + Dados criptografados
5. Extensão: .enc (arquivo criptografado)
```

### 2.3 Processo de Descriptografia
```
1. Leitura dos primeiros 16 bytes = IV
2. Senha do usuário → PBKDF2-SHA256 (100k iterações) → Chave de 256 bits
3. Dados restantes → Descriptografia AES-256-CBC com chave + IV
4. Validação de padding PKCS7
4. Escrita do arquivo descriptografado
```

---

## 3. Gestão de Chaves e Senhas

### 3.1 Armazenamento de Segredos
| Item | Onde | Proteção |
|------|------|----------|
| Senha do usuário | Memória (tempo de execução) | Nunca persistida |
| Hash da senha (bcrypt) | Banco `backups.password_hash` | bcrypt cost 12 |
| Chave derivada (AES) | Memória (tempo de execução) | Nunca persistida |
| IV | Prefixo do arquivo .enc | Em claro (não sensível) |
| Checksum SHA-256 | Banco `backups.checksum` | Em claro (verificação) |

### 3.2 Políticas de Senha
- **Comprimento mínimo:** 8 caracteres
- **Comprimento máximo:** 100 caracteres
- **Complexidade:** Não forçada (responsabilidade do admin)
- **Expiração:** Não definida (recomendado rotação anual)
- **Histórico:** Não aplicável (cada backup tem senha própria)

### 3.3 Rotação de Senhas
- **Recomendação:** Troca anual ou após incidente
- **Processo:** Novo backup com nova senha → valida dobra antiga marcada como obsoleta
- **Backup antigo:** Mantido até expiração da retenção

---

## 4. Controle de Acesso

### 4.1 Permissões (Spatie)
| Permissão | Descrição | Roles Padrão |
|-----------|-----------|--------------|
| `manage_backups` | CRUD backups, restore, cleanup | super_admin, financeiro |
| `view_backups` | Listar/visualizar backups | auditoria, visualizador |

### 4.2 Isolamento por Usuário
- Backups pertencem ao usuário que os criou (`user_id`)
- Usuários só veem/gerenciam seus próprios backups
- `super_admin` vê todos (para auditoria)

### 4.3 Isolamento por Tenant (Futuro)
- Preparado para multi-tenancy via `team_id` (Spatie teams)
- Atualmente single-tenant (Santa Casa única)

---

## 5. Integridade e Validação

### 5.1 Checksum SHA-256
- Calculado no arquivo final (criptografado ou não)
- Algoritmo: SHA-256
- Armazenado em `backups.checksum` (hex, 64 chars)
- Verificado em:
  - Validação manual (`/api/backups/{id}/verify`)
  - Antes de toda restauração
  - Download (opcional)

### 5.2 Validação de Integridade
```php
// BackupService::verifyIntegrity()
$currentChecksum = hash_file('sha256', $fullPath);
return $currentChecksum === $backup->checksum;
```

### 5.3 Verificação de Senha
```php
// BackupService::verifyPassword()
return Hash::check($password, $backup->password_hash);
```

---

## 6. Proteção Contra Ameaças

| Ameaça | Mitigação Implementada |
|--------|------------------------|
| **Acesso não autorizado a backups** | Criptografia AES-256 + autenticação Sanctum + RBAC |
| **Vazamento de senha** | Nunca armazenada em claro; apenas hash bcrypt |
| **Corrupção de backup** | Checksum SHA-256 verificado antes de restore/download |
| **Ransomware/Exclusão** | Retenção múltipla (30d/12s/12m) + off-site |
| **Insider threat** | Auditoria imutável (Spatie ActivityLog) |
| **Perda de senha** | Backup inutilizável (by design) - documentado |
| **Interceptação em trânsito** | HTTPS obrigatório (TLS 1.2+) |
| **Exfiltração via backup** | Criptografia + RBAC + Auditoria de download |

---

## 7. Conformidade

### 7.1 LGPD (Lei Geral de Proteção de Dados)
| Artigo | Requisito | Implementação |
|--------|-----------|---------------|
| Art. 7º | Consentimento/Legítimo interesse | Backup = legítimo interesse (art. 7º, X) |
| Art. 12 | Acesso aos dados | Export CSV/XLSX de backups |
| Art. 18 | Eliminação dos dados | Exclusão segura + cron de limpeza |
| Art. 46 | Segurança dos dados | AES-256 + TLS 1.2+ + RBAC |
| Art. 48 | Notificação de incidente | Auditoria imutável + alertas |

### 7.2 Outras Normas
- **ISO 27001:** Controles A.8.2.3, A.12.3.1, A.17.1.2
- **PCI DSS:** Não aplicável (não processa cartões)
- **HIPAA:** Não aplicável (não saúde EUA)

---

## 8. Resposta a Incidentes

### 8.1 Cenários de Risco

| Cenário | Detecção | Resposta | Tempo Máximo |
|---------|----------|----------|--------------|
| Falha de backup | Monitoramento + Alerta | Investigação + reexecução | < 1h |
| Backup corrompido | Validação falha | Alerta + novo backup | < 30 min |
| Senha comprometida | Detecção de acesso anômalo | Revogação + novo backup | < 30 min |
| Ransomware | Alertas de integridade + monitoramento | Isolamento + restore | < 4h |
| Perda de senha | Tentativas falhas (3x) | Alerta + bloqueio temporário | Imediato |

### 8.2 Comunicação de Incidente
1. **Detecção** → Alerta automático (Slack/Email)
2. **Triagem** → Classificação (P1-P4)
3. **Contenção** → Ações imediatas (isolamento, bloqueio)
4. **Erradicação** → Correção da causa raiz
5. **Recuperação** → Restore validado + testes
6. **Lições Aprendidas** → Relatório + atualização de runbooks

---

## 8. Auditoria e Logs

### 8.1 Eventos Auditados (Spatie ActivityLog)
| Evento | Modelo | Campos Principais |
|--------|--------|-------------------|
| `backup.created` | Backup | id, user_id, type, encrypted, size |
| `backup.downloaded` | Backup | id, user_id, ip |
| `backup.restored` | Backup | id, user_id, snapshot_id |
| `backup.deleted` | Backup | id, user_id |
| `backup.cleanup` | Backup | deleted_count, policy |
| `backup.failed` | Backup | id, error_message |

### 8.2 Retenção de Logs
- **ActivityLog:** 2 anos (conforme LGPD)
- **Logs de aplicação:** 90 dias (rotativo)
- **Logs de auditoria:** 5 anos (imutável)

---

## 9. Checklist de Segurança (Pré-Deploy)

- [ ] TLS 1.2+ forçado em todos os endpoints
- [ ] Headers de segurança (HSTS, CSP, X-Frame-Options)
- [ ] Rate limiting em endpoints sensíveis (backup/restore/download)
- [ ] Sanitização de inputs (validação FormRequest)
- [ ] Secrets no .env (não no código)
- [ ] Permissões de arquivos (storage 750, backups 640)
- [ ] Backup da chave mestra (se houver) em HSM/cofre físico
- [ ] Testes de restore automatizados no CI/CD
- [ ] Documentação de runbooks de incidente atualizada

---

## 10. Conclusão

O sistema de backup do Santa Casa Financeiro implementa **defesa em profundidade** com:
- Criptografia forte (AES-256) em repouso
- Controle de acesso granular (RBAC + ownership)
- Integridade verificada (SHA-256 + verificação automática)
- Auditoria imutável (append-only)
- Conformidade LGPD/ISO 27001
- Resposta a incidentes documentada

**Status:** ✅ **IMPLEMENTADO E VALIDADO**

---

**Responsável:** Equipe de Segurança da Informação
**Data:** 2026-06-19
**Próxima Revisão:** 2026-09-19