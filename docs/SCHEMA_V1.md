# SCHEMA_V1.md - Documentacao do Schema MySQL

## Visao Geral

Dual-layer architecture:
- **historico** (read-only): Dados migrados da fonte legada
- **operacional** (2026+): Novos dados do sistema financeiro moderno

## Tabelas HISTORICO (13)

1. historico_fornecedores
2. historico_contas
3. historico_saldos
4. historico_notas
5. historico_baixas
6. historico_operacoes_banco
7. historico_caixa
8. historico_movimento_caixa
9. historico_cheque_caixa
10. historico_baixas_perdidas
11. historico_convenios
12. historico_usuarios
13. historico_tipo_conta

## Tabelas OPERACIONAL (7)

1. receivables
2. provisions
3. cash_registers
4. cash_movements
5. bank_investments
6. bank_operations
7. export_jobs

## Alteracoes em Tabelas Existentes (2)

1. payables - add legacy_nota_id, receivable_id
2. bank_accounts - add canonical_agency, canonical_account, classification
