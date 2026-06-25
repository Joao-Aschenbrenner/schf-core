import { api } from './api'

export interface SupplierReportData {
  suppliers: {
    supplier_id: number
    supplier_name: string
    total_amount: number
    total_paid: number
    total_pending: number
    total_overdue: number
    count: number
  }[]
  summary: {
    total_suppliers: number
    total_amount: number
    total_paid: number
    total_pending: number
  }
}

export interface CashFlowData {
  period: { from: string; to: string }
  outflows: { paid: number; pending: number; total: number }
  by_method: Record<string, { count: number; total: number }>
  daily_flow: { date: string; paid: number }[]
}

export interface PrestacaoContasData {
  health_plan: { id: number; name: string; code: string } | null
  period: { from: string; to: string }
  entries: {
    id: number
    description: string
    supplier: string | null
    category: string | null
    amount: number
    paid_amount: number
    due_date: string
    payment_date: string | null
    status: string
  }[]
  summary: {
    total_entries: number
    total_amount: number
    total_paid: number
    total_pending: number
  }
}

export const reportService = {
  async supplierReport(params?: Record<string, any>): Promise<SupplierReportData> {
    const response = await api.get('/reports/suppliers', { params })
    return response.data.data
  },

  async categoryReport(params?: Record<string, any>): Promise<any> {
    const response = await api.get('/reports/categories', { params })
    return response.data.data
  },

  async planReport(params?: Record<string, any>): Promise<any> {
    const response = await api.get('/reports/plans', { params })
    return response.data.data
  },

  async cashFlowReport(params?: Record<string, any>): Promise<CashFlowData> {
    const response = await api.get('/reports/cash-flow', { params })
    return response.data.data
  },

  async prestacaoContas(params?: Record<string, any>): Promise<PrestacaoContasData> {
    const response = await api.get('/reports/prestacao-contas', { params })
    return response.data.data
  },
}
