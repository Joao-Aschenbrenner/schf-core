# Guia do UsuÃ¡rio â€” SCHF

**Projeto:** Sistema Financeiro â€” SCHF  
**VersÃ£o:** 0.2.0  
**Data:** 14 de Junho de 2026

---

## SumÃ¡rio

1. [Login e Logout](#1-login-e-logout)
2. [Dashboard](#2-dashboard)
3. [Fornecedores](#3-fornecedores)
4. [Planos de SaÃºde](#4-planos-de-saÃºde)
5. [Categorias de Despesa](#5-categorias-de-despesa)
6. [Contas BancÃ¡rias](#6-contas-bancÃ¡rias)
7. [NF-e (Nota Fiscal EletrÃ´nica)](#7-nf-e)
8. [Contas a Pagar](#8-contas-a-pagar)
9. [PrÃ©-lanÃ§amentos](#9-prÃ©-lanÃ§amentos)
10. [ConciliaÃ§Ã£o](#10-conciliaÃ§Ã£o)
11. [Trilha de Auditoria](#11-trilha-de-auditoria)
12. [RelatÃ³rios](#12-relatÃ³rios)

---

## 1. Login e Logout

### Fazer Login

1. Acesse o sistema pelo navegador (http://localhost:1420) ou pelo atalho do desktop
2. Na tela de login, insira seu **email** e **senha**
3. O indicador de status do backend serÃ¡ exibido abaixo do formulÃ¡rio
4. Clique em **Entrar**
5. VocÃª serÃ¡ redirecionado para o **Dashboard**

**Credenciais padrÃ£o:**
- Email: `admin@schf.com`
- Senha: `password`

> **Dica:** A sessÃ£o expira apÃ³s 12 horas de inatividade. VocÃª precisarÃ¡ fazer login novamente.

### Fazer Logout

1. Clique no botÃ£o de **Logout** localizado na sidebar ou no cabeÃ§alho
2. VocÃª serÃ¡ redirecionado para a tela de login
3. Sua sessÃ£o serÃ¡ encerrada

---

## 2. Dashboard

O Dashboard Ã© a tela inicial do sistema, exibindo um resumo das operaÃ§Ãµes financeiras.

### KPIs Principais

| Indicador | DescriÃ§Ã£o |
|-----------|-----------|
| Total de Despesas | Soma de todas as despesas no perÃ­odo |
| Total de Receitas | Soma de todas as receitas no perÃ­odo |
| Saldo | DiferenÃ§a entre receitas e despesas |
| Contas a Pagar | Total de pagamentos pendentes |

### GrÃ¡ficos

- **EvoluÃ§Ã£o Mensal:** GrÃ¡fico de linha mostrando receitas vs despesas
- **Top Fornecedores:** Maiores fornecedores por valor
- **Despesas por Categoria:** DistribuiÃ§Ã£o de despesas

### NavegaÃ§Ã£o

- Use a **sidebar** para acessar todos os mÃ³dulos
- O indicador de **conexao** mostra se o backend esta ativo
- Clique no nome do usuario para acessar opcoes de conta

---

## 3. Fornecedores

Gerencie todos os fornecedores do hospital.

### Listar Fornecedores

1. Acesse **Fornecedores** na sidebar
2. A tabela exibe todos os fornecedores cadastrados
3. Use a **busca** para filtrar por nome ou CNPJ
4. Use os **filtros** para refinar por status, banco ou tipo

### Criar Fornecedor

1. Clique em **Novo Fornecedor**
2. Preencha os campos obrigatÃ³rios:
   - **Nome:** RazÃ£o social do fornecedor
   - **CNPJ:** 00.000.000/0001-00
   - **Telefone:** (00) 00000-0000
   - **Email:** email@fornecedor.com
   - **EndereÃ§o:** Rua, nÃºmero, bairro, cidade, UF, CEP
   - **Banco:** Nome do banco
   - **AgÃªncia:** NÃºmero da agÃªncia
   - **Conta:** NÃºmero da conta
   - **Tipo de Conta:** Corrente ou PoupanÃ§a
3. Clique em **Salvar**
4. Uma mensagem de sucesso serÃ¡ exibida

### Editar Fornecedor

1. Na lista de fornecedores, clique no Ã­cone de **Editar** (lÃ¡pis)
2. Altere os campos desejados
3. Clique em **Salvar AlteraÃ§Ãµes**

### Excluir Fornecedor

1. Na lista, clique no Ã­cone de **Excluir** (lixeira)
2. Confirme a exclusÃ£o no modal de confirmaÃ§Ã£o
3. O fornecedor serÃ¡ removido da lista

---

## 4. Planos de SaÃºde

Gerencie os planos de saÃºde contratados pelo hospital.

### Listar Planos

1. Acesse **Planos de SaÃºde** na sidebar
2. A tabela exibe todos os planos cadastrados
3. Filtre por nome, cÃ³digo ou tipo

### Criar Plano

1. Clique em **Novo Plano**
2. Preencha os campos:
   - **Nome:** Nome do plano
   - **CÃ³digo:** CÃ³digo identificador
   - **Tipo:** Individual, Familiar ou Empresarial
   - **CarÃªncia:** PerÃ­odo de carÃªncia em dias
   - **Cobertura:** DescriÃ§Ã£o da cobertura
3. Clique em **Salvar**

### Editar/Excluir Plano

- Siga o mesmo processo dos fornecedores (Ã­cones de editar/excluir na tabela)

---

## 5. Categorias de Despesa

Organize as despesas por categorias.

### Listar Categorias

1. Acesse **Categorias de Despesa** na sidebar
2. Visualize a Ã¡rvore de categorias (hierarquia)

### Criar Categoria

1. Clique em **Nova Categoria**
2. Preencha:
   - **Nome:** Nome da categoria
   - **DescriÃ§Ã£o:** DescriÃ§Ã£o detalhada
   - **CÃ³digo:** CÃ³digo de classificaÃ§Ã£o
   - **Categoria Pai:** (Opcional) Se for subcategoria
3. Clique em **Salvar**

### Editar/Excluir Categoria

- Use os Ã­cones de aÃ§Ã£o na tabela de categorias

---

## 6. Contas BancÃ¡rias

Gerencie as contas bancÃ¡rias do hospital.

### Listar Contas

1. Acesse **Contas BancÃ¡rias** na sidebar
2. Visualize o saldo atual de cada conta
3. Filtre por banco, agÃªncia ou titular

### Criar Conta

1. Clique em **Nova Conta**
2. Preencha:
   - **Banco:** Nome do banco
   - **AgÃªncia:** NÃºmero da agÃªncia
   - **Conta:** NÃºmero da conta
   - **Tipo:** Corrente ou PoupanÃ§a
   - **Titular:** Nome do titular
   - **Saldo Inicial:** Valor inicial (opcional)
3. Clique em **Salvar**

### Editar/Excluir Conta

- Use os Ã­cones de aÃ§Ã£o na tabela

---

## 7. NF-e

Gerencie Notas Fiscais EletrÃ´nicas.

### Upload de NF-e

1. Acesse **NF-e** na sidebar
2. Clique em **Importar NF-e**
3. Selecione o arquivo XML da nota fiscal
4. O sistema validarÃ¡ e extrairÃ¡ os dados automaticamente
5. Revise os dados extraÃ­dos
6. Confirme a importaÃ§Ã£o

### Dados ExtraÃ­dos

- Emitente (CNPJ, razÃ£o social, endereÃ§o)
- DestinatÃ¡rio
- Produtos/ServiÃ§os
- Valores (base de cÃ¡lculo, ICMS, ISS, total)
- Chave de acesso

### Listar NF-e

1. A tabela exibe todas as NF-e importadas
2. Filtre por perÃ­odo, fornecedor ou status
3. Clique em uma NF-e para ver os detalhes

---

## 8. Contas a Pagar

Gerencie os pagamentos a fornecedores.

### Listar Pagamentos

1. Acesse **Contas a Pagar** na sidebar
2. Filtre por perÃ­odo, fornecedor, status ou categoria
3. Status disponÃ­veis: **Pendente**, **Agendado**, **Pago**, **Cancelado**

### Criar Pagamento

1. Clique em **Novo Pagamento**
2. Preencha:
   - **Fornecedor:** Selecione o fornecedor
   - **Valor:** Valor do pagamento
   - **Data de Vencimento:** Data prevista
   - **Categoria:** Categoria de despesa
   - **DescriÃ§Ã£o:** ObservaÃ§Ã£o do pagamento
   - **NF-e:** (Opcional) Vincular nota fiscal
3. Clique em **Salvar**

### Alterar Status

1. Na lista, clique em um pagamento
2. Altere o status conforme o fluxo:
   - **Pendente** â†’ **Agendado** â†’ **Pago**
   - **Pendente** â†’ **Cancelado**
3. Confirme a alteraÃ§Ã£o

---

## 9. PrÃ©-lanÃ§amentos

Registre despesas antes da confirmaÃ§Ã£o final.

### Criar PrÃ©-lanÃ§amento

1. Acesse **PrÃ©-lanÃ§amentos** na sidebar
2. Clique em **Novo PrÃ©-lanÃ§amento**
3. Preencha os dados da despesa
4. O prÃ©-lanÃ§amento ficarÃ¡ com status **Pendente**

### Aprovar PrÃ©-lanÃ§amento

1. Selecione o prÃ©-lanÃ§amento
2. Revise os dados
3. Clique em **Aprovar**
4. O prÃ©-lanÃ§amento serÃ¡ convertido em pagamento

### Rejeitar PrÃ©-lanÃ§amento

1. Selecione o prÃ©-lanÃ§amento
2. Clique em **Rejeitar**
3. Informe o motivo da rejeiÃ§Ã£o

---

## 10. ConciliaÃ§Ã£o

Concilie os lanÃ§amentos com o extrato bancÃ¡rio.

### Processo de ConciliaÃ§Ã£o

1. Acesse **ConciliaÃ§Ã£o** na sidebar
2. O sistema exibe lanÃ§amentos pendentes de conciliaÃ§Ã£o
3. Para cada lanÃ§amento:
   - Verifique se corresponde a uma transaÃ§Ã£o bancÃ¡ria
   - Se corresponder, clique em **Conciliar**
   - Se nÃ£o corresponder, marque como **Divergente**

### Status de ConciliaÃ§Ã£o

| Status | DescriÃ§Ã£o |
|--------|-----------|
| Pendente | Aguardando conciliaÃ§Ã£o |
| Conciliado | TransaÃ§Ã£o confirmada pelo banco |
| Divergente | TransaÃ§Ã£o nÃ£o encontrada no banco |

### Resolver DivergÃªncias

1. Clique em uma transaÃ§Ã£o com status **Divergente**
2. Analise os dados
3. OpÃ§Ãµes:
   - **Conciliar manualmente** se for erro de registro
   - **Manter como divergente** para investigaÃ§Ã£o
   - **Cancelar** se for lanÃ§amento invÃ¡lido

---

## 11. Trilha de Auditoria

Visualize todas as aÃ§Ãµes realizadas no sistema.

### Acessar a Trilha

1. Acesse **Auditoria** na sidebar
2. A tabela exibe todas as atividades registradas

### InformaÃ§Ãµes Exibidas

| Coluna | DescriÃ§Ã£o |
|--------|-----------|
| Data/Hora | Data e hora da aÃ§Ã£o |
| UsuÃ¡rio | Quem realizou a aÃ§Ã£o |
| AÃ§Ã£o | Tipo de aÃ§Ã£o (criou, editou, excluiu, etc.) |
| Entidade | Qual entidade foi afetada |
| Detalhes | Dados antigos e novos |

### Filtros

- **PerÃ­odo:** Data inicial e final
- **UsuÃ¡rio:** Filtrar por responsÃ¡vel
- **AÃ§Ã£o:** Tipo de operaÃ§Ã£o
- **Entidade:** MÃ³dulo afetado

### Exportar

1. Aplique os filtros desejados
2. Clique em **Exportar**
3. Escolha o formato (CSV ou PDF)
4. O arquivo serÃ¡ baixado

---

## 12. RelatÃ³rios

Gere relatÃ³rios gerenciais do sistema.

### Tipos de RelatÃ³rios

| RelatÃ³rio | DescriÃ§Ã£o |
|-----------|-----------|
| Despesas por PerÃ­odo | Total de despesas em um perÃ­odo |
| Despesas por Fornecedor | Ranking de fornecedores |
| Despesas por Categoria | DistribuiÃ§Ã£o por categoria |
| Fluxo de Caixa | Entradas e saÃ­das |
| ConciliaÃ§Ã£o BancÃ¡ria | Status de conciliaÃ§Ã£o |
| Pagamentos | HistÃ³rico de pagamentos |

### Gerar RelatÃ³rio

1. Acesse **RelatÃ³rios** na sidebar
2. Selecione o tipo de relatÃ³rio
3. Defina os filtros (perÃ­odo, fornecedor, categoria, etc.)
4. Clique em **Gerar**
5. Visualize na tela ou exporte

### Exportar RelatÃ³rio

1. ApÃ³s gerar o relatÃ³rio
2. Clique em **Exportar**
3. Escolha o formato:
   - **PDF:** Para impressÃ£o
   - **CSV:** Para planilhas
   - **Excel:** Para anÃ¡lise detalhada

---

## Atalhos de Teclado

| Atalho | AÃ§Ã£o |
|--------|------|
| `Ctrl + K` | Busca global |
| `Ctrl + S` | Salvar formulÃ¡rio |
| `Esc` | Fechar modal |
| `Tab` | PrÃ³ximo campo |
| `Shift + Tab` | Campo anterior |

---

## Dicas

- **Salve frequentemente** ao preencher formulÃ¡rios longos
- **Use a busca** para encontrar registros rapidamente
- **Verifique o status** do backend no indicador da sidebar
- **Exporte relatÃ³rios** regularmente para backup
- **Auditoria** registra todas as aÃ§Ãµes â€” use para investigar problemas

---

*Guia gerado por: opencode â€” 14 de Junho de 2026*


