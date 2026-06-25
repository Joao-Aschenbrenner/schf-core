# Smoke Tests â€” SCHF Desktop

## Requisitos

- App instalado ou rodando via `npm run tauri:dev`
- Backend rodando (Docker ou VPS)

## Testes

### 1. Abrir aplicativo pela primeira vez

**Passos:**
1. Execute o app (atalho ou tauri dev)
2. Verifique se a janela abre com tÃ­tulo "SCHF"
3. Verifique se a janela tem tamanho 1280x800
4. Verifique se a janela estÃ¡ centralizada na tela

**Resultado esperado:**
- Janela abre corretamente
- Tela de login ou de configuraÃ§Ã£o de conexÃ£o aparece

---

### 2. Backend offline â€” Erro amigÃ¡vel

**Passos:**
1. Com o backend PARADO, abra o app
2. Observe a tela de login

**Resultado esperado:**
- Badge mostra "Offline"
- Mensagem clara: "Servidor indisponÃ­vel"
- BotÃ£o "Entrar" estÃ¡ desabilitado
- Link "Configurar conexÃ£o" estÃ¡ visÃ­vel
- NÃ£o hÃ¡ crash ou tela branca

---

### 3. Trocar API_URL entre ambientes

**Passos:**
1. Clique em "Configurar conexÃ£o" (ou vÃ¡ para /conexao)
2. Clique no preset "Rede interna (192.168.0.10)"
3. Clique em "Salvar e Testar"
4. Volte para "Local (localhost)"
5. Salve e teste novamente

**Resultado esperado:**
- URL muda ao clicar nos presets
- Ao salvar, o health check Ã© executado
- Badge mostra resultado do teste
- URL Ã© persistida (fechar e reabrir mantÃ©m a configuraÃ§Ã£o)

---

### 4. Autenticar usuÃ¡rio vÃ¡lido

**Passos:**
1. Com o backend rodando, vÃ¡ para tela de login
2. Insira credenciais vÃ¡lidas
3. Clique em "Entrar"

**Resultado esperado:**
- Badge mostra "Online"
- Login redireciona para Dashboard
- Nome e email do usuÃ¡rio aparecem na sidebar
- Menu de navegaÃ§Ã£o funciona

---

### 5. Falhar com credenciais invÃ¡lidas

**Passos:**
1. VÃ¡ para tela de login
2. Insira e-mail vÃ¡lido e senha incorreta
3. Clique em "Entrar"

**Resultado esperado:**
- Mensagem clara: "Credenciais invÃ¡lidas"
- UsuÃ¡rio permanece na tela de login
- Campos nÃ£o sÃ£o limpos (pode corrigir e tentar novamente)

---

### 6. Reiniciar app e manter sessÃ£o

**Passos:**
1. FaÃ§a login com sucesso
2. Feche o app completamente
3. Reabra o app

**Resultado esperado:**
- App carrega diretamente no Dashboard
- NÃ£o pede login novamente (sessÃ£o ainda vÃ¡lida)
- SessÃ£o expira apÃ³s 12 horas ou ao clicar "Sair"

---

### 7. SessÃ£o expirada

**Passos:**
1. No localStorage do app, altere `schf-auth` para `{"state":{"token":"test","isAuthenticated":true,"expiresAt":0}}`
2. Reabra o app

**Resultado esperado:**
- App detecta sessÃ£o expirada
- Redireciona para tela de login
- Token Ã© removido do storage

---

### 8. NavegaÃ§Ã£o completa

**Passos:**
1. ApÃ³s login, navegue para:
   - Fornecedores
   - ConvÃªnios
   - Contas BancÃ¡rias
   - Categorias
2. Volte ao Dashboard

**Resultado esperado:**
- Todas as pÃ¡ginas carregam sem erro
- Sidebar destaca a pÃ¡gina ativa
- Dados sÃ£o carregados do backend
- Erros de API sÃ£o tratados sem crash

---

### 9. Logout

**Passos:**
1. Clique em "Sair" na sidebar
2. Aguarde redirecionamento

**Resultado esperado:**
- Token Ã© removido
- Redireciona para tela de login
- Cache de queries Ã© limpo

---

### 10. Atalho do instalador (apÃ³s build)

**Passos:**
1. Instale via NSIS
2. Verifique atalhos no Menu Iniciar e Ãrea de Trabalho
3. Execute pelo atalho

**Resultado esperado:**
- Atalhos criados corretamente
- Ãcone aparece no atalho
- App abre normalmente pelo atalho
- App aparece em "Adicionar/Remover Programas"


