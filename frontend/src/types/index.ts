export interface User {
  id: number
  name: string
  email: string
  is_active: boolean
  roles: string[]
  permissions: string[]
}

export interface Supplier {
  id: number
  name: string
  cnpj: string | null
  cpf: string | null
  trade_name: string | null
  ie: string | null
  im: string | null
  cnae: string | null
  email: string | null
  phone: string | null
  cellphone: string | null
  contact_name: string | null
  address_street: string | null
  address_number: string | null
  address_complement: string | null
  address_district: string | null
  address_city: string | null
  address_state: string | null
  address_zip: string | null
  bank_name: string | null
  bank_agency: string | null
  bank_account: string | null
  bank_type: string | null
  pix_key: string | null
  pix_type: string | null
  notes: string | null
  is_active: boolean
  legacy_id: number | null
}

export interface HealthPlan {
  id: number
  name: string
  code: string
  type: string
  balance: number
  committed_balance: number
  is_active: boolean
}

export interface ExpenseCategory {
  id: number
  name: string
  code: string
  parent_id: number | null
  is_active: boolean
}

export interface BankAccount {
  id: number
  bank_code: string
  bank_name: string
  agency: string
  account: string
  type: string
  current_balance: number
  is_active: boolean
}

export interface Nfe {
  id: number
  nfe_key: string | null
  nfe_number: string
  serie: string | null
  emission_date: string
  entry_date: string | null
  supplier_id: number | null
  health_plan_id: number | null
  expense_category_id: number | null
  goods_value: number
  service_value: number
  insurance_value: number
  other_value: number
  icms_value: number
  ipi_value: number
  pis_value: number
  cofins_value: number
  total_value: number
  description: string | null
  status: 'draft' | 'confirmed' | 'cancelled'
  is_manual_entry: boolean
  supplier?: Supplier
  health_plan?: HealthPlan
  expense_category?: ExpenseCategory
  items?: NfeItem[]
}

export interface NfeItem {
  id: number
  nfe_id: number
  product_code: string | null
  description: string
  unit: string | null
  quantity: number
  unit_value: number
  total_value: number
  icms_value: number
  ipi_value: number
}

export interface Payable {
  id: number
  description: string
  document_number: string | null
  supplier_id: number | null
  nfe_id: number | null
  health_plan_id: number | null
  expense_category_id: number | null
  bank_account_id: number | null
  amount: number
  discount: number
  interest: number
  paid_amount: number
  due_date: string
  payment_date: string | null
  paid_at: string | null
  status: 'draft' | 'pending' | 'scheduled' | 'paid' | 'cancelled' | 'overdue'
  payment_method: string | null
  bar_code: string | null
  receipt_number: string | null
  notes: string | null
  cancellation_reason: string | null
  created_by: number | null
  approved_by: number | null
  approved_at: string | null
  supplier?: Supplier
  nfe?: Nfe
  health_plan?: HealthPlan
  expense_category?: ExpenseCategory
  bank_account?: BankAccount
}

export interface PreLaunch {
  id: number
  description: string
  type: 'payroll' | 'medical_fees' | 'tax' | 'supplier' | 'recurring'
  health_plan_id: number | null
  supplier_id: number | null
  expense_category_id: number | null
  amount: number
  due_date: string
  status: 'draft' | 'confirmed' | 'converted' | 'cancelled'
  notes: string | null
  health_plan?: HealthPlan
  supplier?: Supplier
  expense_category?: ExpenseCategory
}

export interface HistoricoFornecedor {
  id: number
  nome: string
  cnpj: string | null
  cpf: string | null
  nome_fantasia: string | null
  ativo: boolean
  legacy_id: number | null
}

export interface HistoricoConta {
  id: number
  banco: string
  agencia: string
  conta_corrente: string
  classificacao: string
  tipo: string
  status: string
  saldo_atual: number
  legacy_id: number | null
}

export interface HistoricoTipoConta {
  id: number
  nome: string
  classificacao: string
}

export interface HistoricoSaldo {
  id: number
  conta_id: number
  data: string
  saldo: number
  tipo: string
}

export interface HistoricoNota {
  id: number
  fornecedor_id: number | null
  fornecedor?: HistoricoFornecedor
  documento: string
  cnpj_cpf: string | null
  valor: number
  emissao: string | null
  vencimento: string | null
  pagamento: string | null
  situacao: 'baixada' | 'em_aberto' | 'baixa_perdida' | 'cancelada'
  rcb_pgt: 'R' | 'P'
  forma: string | null
  baixas?: HistoricoBaixa[]
  legacy_id: number | null
}

export interface HistoricoBaixa {
  id: number
  nota_id: number | null
  nota?: HistoricoNota
  conta_id: number | null
  conta?: HistoricoConta
  data: string
  tipo_baixa: string
  valor: number
  historico: string | null
  forma: string | null
  legacy_id: number | null
}

export interface HistoricoOperacaoBanco {
  id: number
  conta_id: number | null
  conta?: HistoricoConta
  data: string
  tipo: string
  descricao: string
  credito: number
  debito: number
  saldo_acumulado: number
  legacy_id: number | null
}

export interface HistoricoCaixa {
  id: number
  data: string
  operador: string
  saldo_abertura: number
  saldo_fechamento: number | null
  total_creditos: number
  total_debitos: number
  status: 'aberto' | 'fechado'
  legacy_id: number | null
}

export interface HistoricoMovimentoCaixa {
  id: number
  caixa_id: number
  caixa?: HistoricoCaixa
  tipo: 'credito' | 'debito'
  valor: number
  descricao: string
  categoria: string | null
  forma_pagamento: string | null
  documento: string | null
  data: string
  legacy_id: number | null
}

export interface HistoricoChequeCaixa {
  id: number
  caixa_id: number
  numero: string
  valor: number
  favorecido: string
  data_emissao: string
  data_compensacao: string | null
  status: string
  legacy_id: number | null
}

export interface HistoricoBaixaPerdida {
  id: number
  nota_id: number | null
  nota?: HistoricoNota
  fornecedor_id: number | null
  fornecedor?: HistoricoFornecedor
  documento: string | null
  valor: number
  forma_pr: string | null
  data_pagamento: string | null
  status_revisao: 'pendente' | 'revisada' | 'ignorada'
  observacao: string | null
  conta_correta_id: number | null
  conta_correta?: HistoricoConta
  baixa_correta_id: number | null
  baixa_correta?: HistoricoBaixa
  legacy_id: number | null
}

export interface HistoricoConvenio {
  id: number
  nome: string
  codigo: string
  tipo: string
  saldo: number
  ativo: boolean
  legacy_id: number | null
}

export interface HistoricoUsuario {
  id: number
  nome: string
  email: string
  ativo: boolean
  legacy_id: number | null
}

export interface ExtratoBancario {
  conta: HistoricoConta
  saldo_inicial: number
  operacoes: HistoricoOperacaoBanco[]
  saldo_final: number
  total_creditos?: number
  total_debitos?: number
}

export interface ExtratoCaixa {
  caixa: HistoricoCaixa
  movimentos: HistoricoMovimentoCaixa[]
  totais: {
    total_creditos: number
    total_debitos: number
    saldo_final: number
  }
}

export interface Receivable {
  id: number
  description: string
  supplier_id: number | null
  supplier?: Supplier
  health_plan_id: number | null
  health_plan?: HealthPlan
  amount: number
  discount: number
  interest: number
  received_amount: number
  due_date: string
  received_date: string | null
  received_at: string | null
  status: 'pending' | 'received' | 'cancelled' | 'overdue'
  payment_method: string | null
  notes: string | null
  created_by: number | null
  approved_by: number | null
  approved_at: string | null
}

export interface Provision {
  id: number
  description: string
  supplier_id: number | null
  supplier?: Supplier
  health_plan_id: number | null
  health_plan?: HealthPlan
  expense_category_id: number | null
  expense_category?: ExpenseCategory
  amount: number
  due_date: string
  status: 'draft' | 'confirmed' | 'paid' | 'cancelled'
  tipo: 'payroll' | 'medical_fees' | 'tax' | 'supplier' | 'recurring'
  notes: string | null
  payable_id: number | null
  created_by: number | null
  confirmed_by: number | null
  confirmed_at: string | null
}

export interface CashRegister {
  id: number
  date: string
  opening_balance: number
  closing_balance: number | null
  total_credits: number
  total_debits: number
  operator: string
  operator_id: number | null
  status: 'open' | 'closed'
  movements?: CashMovement[]
}

export interface CashMovement {
  id: number
  cash_register_id: number
  type: 'credit' | 'debit'
  amount: number
  description: string
  category: string | null
  payment_method: string | null
  document: string | null
  created_at: string
  cash_register?: CashRegister
}

export interface BankInvestment {
  id: number
  bank_account_id: number
  bank_account?: BankAccount
  description: string
  type: string
  balance: number
  initial_amount: number
  rate: number
  start_date: string
  maturity_date: string | null
  status: 'active' | 'redeemed' | 'matured'
  redeemed_at: string | null
  redeemed_amount: number | null
}

export interface BankOperation {
  id: number
  bank_account_id: number
  bank_account?: BankAccount
  date: string
  type: 'application' | 'redemption' | 'transfer_in' | 'transfer_out' | 'fee' | 'interest' | 'other'
  description: string
  amount: number
  target_account_id: number | null
  target_account?: BankAccount
}

export interface ExportJob {
  id: number
  type: string
  status: 'pending' | 'processing' | 'completed' | 'failed'
  file_path: string | null
  file_name: string | null
  params: Record<string, any> | null
  created_at: string
  completed_at: string | null
  error: string | null
}

export interface ApiResponse<T> {
  data: T
  message?: string
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}
