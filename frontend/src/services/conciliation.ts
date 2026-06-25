import { api } from './api'
import type { PaginatedResponse } from '../types'

export interface BankStatement {
  id: number
  bank_account_id: number
  statement_date: string
  source_type: string
  opening_balance: number
  closing_balance: number
  status: string
  bank_account?: any
  items?: BankStatementItem[]
}

export interface BankStatementItem {
  id: number
  bank_statement_id: number
  transaction_date: string
  description: string
  document_id: string | null
  type: 'credit' | 'debit'
  amount: number
  balance_after: number | null
  payable_id: number | null
  is_reconciled: boolean
  reconciled_at: string | null
}

export const conciliationService = {
  async listStatements(params?: Record<string, any>): Promise<PaginatedResponse<BankStatement>> {
    const response = await api.get('/conciliation/statements', { params })
    return response.data
  },

  async getStatement(id: number): Promise<BankStatement> {
    const response = await api.get(`/conciliation/statements/${id}`)
    return response.data.data
  },

  async importOfx(bankAccountId: number, ofxContent: string): Promise<BankStatement> {
    const response = await api.post('/conciliation/import-ofx', {
      bank_account_id: bankAccountId,
      ofx_content: ofxContent,
    })
    return response.data.data
  },

  async conciliate(statementItemId: number, payableId: number): Promise<BankStatementItem> {
    const response = await api.post('/conciliation/conciliate', {
      statement_item_id: statementItemId,
      payable_id: payableId,
    })
    return response.data.data
  },

  async unmatch(statementItemId: number): Promise<BankStatementItem> {
    const response = await api.post(`/conciliation/unmatch/${statementItemId}`)
    return response.data.data
  },

  async autoMatch(statementId: number): Promise<{ matched_count: number }> {
    const response = await api.post(`/conciliation/auto-match/${statementId}`)
    return response.data.data
  },
}
