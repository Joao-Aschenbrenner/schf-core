import { api } from './api'

export interface CronogramaData {
  summary: {
    current_balance: number
    total_pending_outflows: number
    total_projected_inflows: number
  }
  projections: {
    horizon_days: number
    target_date: string
    current_balance: number
    outflows: number
    inflows: number
    projected_balance: number
  }[]
  daily_breakdown: {
    date: string
    outflows: number
    running_balance: number
  }[]
}

export const cronogramaService = {
  async get(params?: Record<string, any>): Promise<CronogramaData> {
    const response = await api.get('/cronograma', { params })
    return response.data.data
  },
}
