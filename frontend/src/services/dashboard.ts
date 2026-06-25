import { api } from './api'

export interface DashboardSummary {
  total_balance: number
  pending_payables: number
  overdue_payables: number
  due_today: number
  active_health_plans: number
  total_suppliers: number
  total_bank_accounts: number
  pending_pre_launches: number
}

export const dashboardService = {
  async getSummary(): Promise<DashboardSummary> {
    const response = await api.get('/dashboard/summary')
    return response.data.data
  },
}
