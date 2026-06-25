# Build e InstalaÃ§Ã£o â€” SCHF Desktop

## PrÃ©-requisitos

### Windows (desenvolvimento e build)

1. **Node.js 20+** â€” https://nodejs.org/
2. **Rust (stable)** â€” https://rustup.rs/
   ```powershell
   curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh
   ```
3. **Microsoft Visual Studio C++ Build Tools**
   - Instale via Visual Studio Installer
   - Selecione "Desktop development with C++"
4. **WebView2** â€” JÃ¡ incluÃ­do no Windows 11. Para Windows 10, instale via:
   - https://developer.microsoft.com/en-us/microsoft-edge/webview2/

### DependÃªncias do projeto

```powershell
cd frontend
npm install
```

## Desenvolvimento local

### Com Docker (backend + banco)

```powershell
cd schf-core
docker-compose up -d
docker-compose exec backend composer install
docker-compose exec backend php artisan key:generate
docker-compose exec backend php artisan migrate --seed
```

### Modo desktop (Tauri dev)

```powershell
cd frontend
npm run tauri:dev
```

Isso inicia o Vite (frontend) e o Tauri (Rust) simultaneamente.
O app abre como janela nativa do Windows.

### Modo browser (desenvolvimento web)

```powershell
cd frontend
npm run dev
```

Acesse: http://localhost:1420

## Build de produÃ§Ã£o

### Gerar instalador Windows

```powershell
cd frontend
npm run tauri:build
```

Isso gera dois instaladores em `frontend/src-tauri/target/release/bundle/`:

- **NSIS** â€” `nsis/SCHF_0.1.0_x64-setup.exe`
  - Instalador comum com assistente de instalaÃ§Ã£o
  - Cria atalho no menu Iniciar e Ã¡rea de trabalho
  - Permite desinstalaÃ§Ã£o via Painel de Controle

- **MSI** â€” `msi/SCHF_0.1.0_x64_en-US.msi`
  - Instalador Windows Installer
  - DistribuiÃ§Ã£o via GPO em ambientes corporativos

### Estrutura de saÃ­da

```
src-tauri/target/release/
â”œâ”€â”€ schf-core.exe       â† ExecutÃ¡vel standalone
â””â”€â”€ bundle/
    â”œâ”€â”€ nsis/
    â”‚   â””â”€â”€ SCHF_0.1.0_x64-setup.exe
    â””â”€â”€ msi/
        â””â”€â”€ SCHF_0.1.0_x64_en-US.msi
```

## InstalaÃ§Ã£o no computador do usuÃ¡rio

### Via NSIS (recomendado)

1. Execute `SCHF_0.1.0_x64-setup.exe`
2. Siga o assistente de instalaÃ§Ã£o
3. O app serÃ¡ instalado em `C:\Users\<usuario>\AppData\Local\SCHF\`
4. Atalhos sÃ£o criados no Menu Iniciar e Ãrea de Trabalho
5. Execute "SCHF" pelo atalho

### Primeira execuÃ§Ã£o

1. O app abre a tela de configuraÃ§Ã£o de conexÃ£o
2. Se o backend estiver em `localhost:8080`, ele detecta automaticamente
3. Caso contrÃ¡rio, configure a URL do backend (IP da rede interna ou VPS)
4. Clique em "Verificar ConexÃ£o"
5. ApÃ³s confirmaÃ§Ã£o, farelogin com credenciais do sistema

## ConfiguraÃ§Ã£o de ambientes

O app suporta 3 modos de conexÃ£o:

| Ambiente | URL exemplo | Uso |
|----------|------------|-----|
| Local | `http://localhost:8080/api` | Desenvolvimento, demo local |
| Rede interna | `http://192.168.0.10:8080/api` | Hospital, rede local |
| VPS | `https://financeiro.hospital.local/api` | ProduÃ§Ã£o, acesso remoto |

A URL Ã© alterada na tela "Configurar ConexÃ£o" dentro do app.

## Gerando Ã­cones

Antes do primeiro build, gere os Ã­cones a partir de uma imagem fonte:

```powershell
cd frontend
npx tauri icon caminho/para/imagem-fonte.png
```

A imagem deve ser:
- PNG quadrada, mÃ­nimo 1024x1024px
- Representando a marca SCHF
- Sem fundo transparente (recomendado fundo branco)

## Troubleshooting

### "Rust not found"
Instale via rustup: https://rustup.rs/

### "Visual Studio C++ tools not found"
Instale Visual Studio Build Tools com "Desktop development with C++"

### "WebView2 not found" (Windows 10)
Baixe e instale: https://developer.microsoft.com/en-us/microsoft-edge/webview2/

### "Erro de conexÃ£o com backend"
1. Verifique se o Docker estÃ¡ rodando: `docker-compose ps`
2. Verifique se o backend responde: `curl http://localhost:8080/api/health`
3. No app, vÃ¡ em "Configurar ConexÃ£o" e ajuste a URL

### Build lento
O primeiro build compila Rust do zero (~5-10 min). Builds subsequentes sÃ£o incrementais (~1-2 min).

### Tamanho do instalador
O instalador NSIS fica entre 3-5 MB (o app Ã© leve, a maior parte Ã© o WebView2 runtime).


