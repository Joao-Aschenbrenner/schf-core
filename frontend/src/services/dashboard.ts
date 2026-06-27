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

export interface ActivityItem {
  id: number
  action: string
  model_type: string | null
  model_id: string | null
  user_name: string
  created_at: string
}

export interface MonthlyPayable {
  month: string
  total: number
  paid: number
  overdue: number
}

export interface SystemHealthCheck {
  status: string
  latency_ms?: number
  message?: string
  used_bytes?: number
  used_formatted?: string
  info?: string
}

export interface OperationalData {
  summary: {
    total_balance: number
    pending_payables: number
    overdue_payables: number
    due_today: number
    paid_this_month: number
    total_paid_this_month: number
    pending_pre_launches: number
    total_suppliers: number
    active_health_plans: number
    total_bank_accounts: number
  }
  payables_by_status: Record<string, number>
  monthly_payables: MonthlyPayable[]
  recent_activity: ActivityItem[]
  backup: {
    last_backup_at: string | null
    total_backups: number
    last_backup_size: number | null
    last_backup_name: string | null
  }
  license: {
    key: string
    type: string
    status: string
    expires_at: string | null
    customer_name: string | null
  } | null
  system_health: Record<string, SystemHealthCheck>
}

export const dashboardService = {
  async getSummary(): Promise<DashboardSummary> {
    const response = await api.get('/dashboard/summary')
    return response.data.data
  },

  async getOperational(): Promise<OperationalData> {
    const response = await api.get('/dashboard/operational')
    return response.data.data
  },
}
