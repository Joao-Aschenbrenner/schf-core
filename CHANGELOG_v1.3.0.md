# SCHF Core v1.3.0 — Guia de Validação

## Resumo das Alterações

### v1.3.0 — Consolidação e Validação

1. **Integração entre módulos** — Todas as classes registradas como singletons no AppServiceProvider
2. **Testes unitários** — 11 novos arquivos de teste para v1.2.0:
   - FeatureFlagServiceTest
   - VersionCheckerTest
   - SignatureValidatorTest
   - AuditServiceTest
   - ObservabilityServiceTest
   - ReleaseDownloaderTest
   - UpdateServiceTest
   - PluginLoaderTest
   - MigrationManifestTest
   - ValidationEngineTest
   - MigrationCompatibilityCheckerTest
3. **CI/CD Pipeline** — `.github/workflows/ci.yml` com lint, tests, security, Docker build, smoke test
4. **API Docs** — Endpoint `/api/docs` servindo OpenAPI 3.0.3 JSON

---

## Validações Pendentes (Requerem Docker)

### FASE 2: Docker Homologado
```bash
cd schf-core
docker compose down -v
docker compose up --build
# Aguardar todos os containers
docker compose ps  # Todos devem estar "Up"
curl http://localhost:9080/api/health  # Deve retornar {"status":"ok"}
```

### FASE 3: Instalação Límpia
```bash
docker compose down -v
rm -rf backend/storage/app/database.sqlite
docker compose up -d
# Abrir http://localhost:9080
# Seguir Wizard: criar org, admin, finalizar
# Login e navegar pelo Dashboard
```

### FASE 4: Updates
```bash
# Verificar atualização
curl -H "Authorization: Bearer TOKEN" http://localhost:9080/api/admin/updates/check
# Listar versões
curl -H "Authorization: Bearer TOKEN" http://localhost:9080/api/admin/updates/versions
# Histórico
curl -H "Authorization: Bearer TOKEN" http://localhost:9080/api/admin/updates/history
```

### FASE 5: Migração
```bash
# Mover pacote de migração para storage/migration-packages/
# Executar via API
curl -X POST -H "Authorization: Bearer TOKEN" http://localhost:9080/api/admin/migration/run \
  -d '{"package": "santa-casa-migration"}'
# Validar relatório gerado em storage/migration-reports/
```

### FASE 6: Plugins
```bash
# Verificar plugins disponíveis
ls backend/app/Plugins/Connectors/
# Plugin FirebirdConnector deve estar listado
# Verificar logs
docker logs schf-backend | grep -i plugin
```

### FASE 7: Fila
```bash
# Verificar workers
docker exec schf-backend php artisan queue:work --once
# Verificar jobs na fila
docker exec schf-backend php artisan queue:status
```

### FASE 8: Auditoria
```bash
# Realizar CRUD e verificar audit_logs no banco
docker exec schf-backend php artisan tinker
>>> App\Models\AuditLog::latest()->first()
```

### FASE 9: Observabilidade
```bash
curl http://localhost:9080/api/health  # Health check detalhado
# Verificar métricas de sistema
docker exec schf-backend php artisan health:check
```

### FASE 10: API Docs
```bash
curl http://localhost:9080/api/docs  # Deve retornar OpenAPI 3.0.3 JSON
```

### FASE 11: Testes
```bash
cd backend
vendor/bin/phpunit --coverage-text
```

### FASE 12: CI/CD
- Push para `develop` ou `master` dispara pipeline
- Verificar em https://github.com/Joao-Aschenbrenner/schf-core/actions

### FASE 13: Desktop
```bash
cd frontend
npm run tauri build  # Gerar .exe
# Testar instalação, atualização, reparo, desinstalação
```

### FASE 14: Performance
```bash
# Benchmark de API
ab -n 1000 -c 10 http://localhost:9080/api/health
# Benchmark de importação
# Importar CSV com 10k registros e medir tempo
```

---

## Arquivos Modificados (v1.3.0)

- `backend/app/Providers/AppServiceProvider.php` — Singletons para todos os serviços
- `backend/routes/api.php` — Rota `/api/docs` + versão atualizada
- `backend/app/Http/Controllers/Api/ApiDocsController.php` — Novo controller
- `.github/workflows/ci.yml` — Pipeline CI/CD completo
- `tests/Unit/` — 11 novos arquivos de teste

## Próximos Passos

1. Executar validações Docker (FASEs 2-9) manualmente
2. Publicar v1.3.0 com tag `v1.3.0`
3. Criar GitHub Release com changelog
4. Atualizar MASTER_PLAN para v1.4.0