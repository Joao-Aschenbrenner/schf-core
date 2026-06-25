# Executable Validation Report

**Data:** 2026-06-18
**Versao:** 1.0

---

## Resumo Executivo

**Nenhum executavel (.exe/.msi) encontrado no projeto.**

O projeto SCHF eh uma aplicacao **web-based** (Laravel + React + Tauri) que roda em container Docker. Nao ha necessidade de executaveis desktop tradicionais (.exe/.msi) para o uso principal.

---

## 1. Arquitetura da Aplicacao

| Camada | Tecnologia | Executavel? |
|--------|------------|-------------|
| Backend API | Laravel 11 (PHP 8.2) | Nao (PHP-FPM) |
| Frontend SPA | React 18 + Vite + Tauri v2 | **Sim** (Tauri build) |
| Database | MySQL 8.0 | Nao (Container) |
| Cache/Queue | Redis 7 | Nao (Container) |
| Proxy | Nginx | Nao (Container) |

---

## 2. Tauri Desktop App (Unico Executavel Potencial)

### 2.1 Configuracao Tauri (src-tauri/)

| Arquivo | Descricao |
|---------|-----------|
| `Cargo.toml` | Dependencias Rust + config build |
| `tauri.conf.json` | Config janela, seguranca, build |
| `build.rs` | Script build customizado |
| `src/main.rs` | Entry point Rust |

### 2.2 Capacidades Tauri (capabilities/default.json)

```json
{
  "permissions": [
    "core:default",
    "shell:allow-execute",
    "fs:allow-read",
    "fs:allow-write",
    "dialog:allow-open",
    "dialog:allow-save"
  ]
}
```

### 2.3 Build Desktop (Quando Necessario)

```bash
# Pre-requisitos
# - Rust toolchain (rustup)
# - Node.js 18+
# - WebView2 (Windows) / webkit2gtk (Linux)

# Build development
npm run tauri dev

# Build production (gera .exe/.msi)
npm run tauri build

# Outputs esperados:
# - SCHF_0.1.0_x64-setup.exe (NSIS installer)
# - SCHF_0.1.0_x64_en-US.msi (MSI installer)
# - SCHF_0.1.0_x64.exe (portable)
```

---

## 3. Testes de Executavel (Pendentes - Nao Executados)

> **NOTA:** Os testes abaixo **nao foram executados** pois o build desktop nao foi gerado no pipeline atual. O projeto roda 100% via Docker (web).

### 3.1 Cenario: Build & Teste Local (Windows 10/11)

| Teste | Comando | Validacao Esperada |
|-------|---------|-------------------|
| **Build Dev** | `npm run tauri dev` | Janela abre, carrega localhost:1420, HMR funciona |
| **Build Prod** | `npm run tauri build` | Gera .exe + .msi sem erros |
| **Teste 1: EXE Portable** | `.\SCHF_0.1.0_x64.exe` | Abre janela, carrega app, login funciona |
| **Teste 2: Instalador NSIS** | `.\SCHF_0.1.0_x64-setup.exe` | Instala, cria atalho, executa, desinstala limpo |
| **Teste 3: MSI** | `msiexec /i "SCHF_0.1.0_x64_en-US.msi"` | Instala silencioso (`/quiet`), executa, repara, desinstala |

### 3.2 Cenario: Compatibilidade SO

| SO | Versao Minima | Status Build | Status Teste |
|----|---------------|--------------|--------------|
| Windows 10 | 1909+ | Pendente | Pendente |
| Windows 11 | 21H2+ | Pendente | Pendente |
| Windows Server 2019+ | LTSC | Pendente | Pendente |

### 3.3 Cenario: VM Limpa (Sem Dependencias Pre-instaladas)

| VM | WebView2 | Rust | Node.js | Resultado Esperado |
|----|----------|------|---------|-------------------|
| Windows 10 Pro (VM fresh) | Auto-install | N/A (built-in) | N/A (built-in) | App abre, login OK |
| Windows 11 Pro (VM fresh) | Built-in | N/A | N/A | App abre, login OK |

---

## 4. Installer NSIS (installer.nsi)

### 4.1 Configuracao Principal

```nsi
; installer.nsi - Principais secoes
Name "SCHF"
OutFile "SCHF_${VERSION}_x64-setup.exe"
InstallDir "$PROGRAMFILES\SCHF"
RequestExecutionLevel admin

Section "Main"
  SetOutPath "$INSTDIR"
  File /r "dist\*"
  CreateShortcut "$SMPROGRAMS\SCHF.lnk" "$INSTDIR\santa-casa-financeiro.exe"
  WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHF" "DisplayName" "SCHF"
  WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHF" "UninstallString" "$INSTDIR\uninstall.exe"
  WriteUninstaller "$INSTDIR\uninstall.exe"
SectionEnd

Section "Uninstall"
  Delete "$INSTDIR\*"
  RMDir "$INSTDIR"
  Delete "$SMPROGRAMS\SCHF.lnk"
  DeleteRegKey HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHF"
SectionEnd
```

---

## 5. Scripts Auxiliares (installer/)

| Script | Funcao |
|--------|--------|
| `config-network.bat` | Configura firewall/portas (9080, 13306, 1420) |
| `manage-containers.bat` | Start/stop/restart containers Docker |
| `installer.nsi` | Script NSIS para gerar .exe/.msi |

---

## 6. Docker Desktop (Alternativa Producao)

Como a aplicacao roda 100% em containers, o deploy producao recomendado eh via **Docker Compose**:

```bash
# Producao
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Verificacao saude
curl http://localhost:9080/api/health
# {"status":"ok","system":"SCHF","version":"0.2.0"}
```

**Vantagens Docker vs Desktop:**
- Zero dependencias no host (so Docker Engine)
- Mesma imagem dev/staging/prod
- Facil rollback (`docker-compose down && docker-compose up -d`)
- Isolamento total (rede, volumes, secrets)
- Orquestracao nativa (Swarm/K8s)

---

## 6. Conclusao & Recomendacoes

| Item | Status | Acao Necessaria |
|------|--------|-----------------|
| **App Web (Docker)** | âœ… Funcionando | Deploy producao via docker-compose |
| **Tauri Desktop Build** | â³ Pendente | Executar `npm run tauri build` em Windows |
| **Teste .exe/.msi** | â³ Pendente | Executar apos build em Windows 10/11 VM |
| **Assinatura Digital** | â³ Pendente | Certificado EV Code Signing (Microsoft Partner) |
| **Publicacao Microsoft Store** | ðŸ“‹ Futuro | Requer conta Partner + certificacao |

### Proximos Passos Imediatos

1. **Build Desktop**: `cd frontend && npm run tauri build` (em Windows 10/11)
2. **Teste Instalador**: Executar `.exe` e `.msi` em VM Windows limpa
3. **Code Signing**: Aplicar certificado EV Code SignToolkit EV Code Signing
4. **CI/CD Desktop**: Adicionar job `tauri-build` no GitHub Actions (matrix: ubuntu/windows)

---

## 7. Rastreabilidade

| Artefato | Localizacao | Versao |
|----------|-------------|--------|
| Frontend Tauri Source | `frontend/src-tauri/` | 0.1.0 |
| NSIS Script | `installer/installer.nsi` | 1.0 |
| Tauri Config | `frontend/src-tauri/tauri.conf.json` | 2.0 |
| Docker Compose | `docker-compose.yml` | 1.0 |

---

**Conclusao:** A aplicacao **funciona 100% via web (Docker)**. O executavel desktop (Tauri) eh opcional para uso offline/local. Build e testes de `.exe/.msi` sao **pendentes** e devem ser executados em ambiente Windows dedicado antes de release desktop.

**Responsavel:** Equipe DevOps / Frontend
**Data:** 2026-06-18
**Status:** Web OK | Desktop Pendente

