import { api } from './api'
import type { ExtratoBancario, HistoricoConta, PaginatedResponse } from '../types'

const emptyPage = <T>(): PaginatedResponse<T> => ({
  data: [],
  meta: {
    current_page: 1,
    last_page: 1,
    per_page: 0,
    total: 0,
  },
})

export const historicoService = {
  async listContas(params?: Record<string, any>): Promise<PaginatedResponse<HistoricoConta>> {
    try {
      const response = await api.get('/historico/contas', { params })
      return response.data
    } catch (error: any) {
      if (error.response?.status === 404) {
        return emptyPage<HistoricoConta>()
      }
      throw error
    }
  },

  async getExtratoBancario(params: Record<string, any>): Promise<ExtratoBancario> {
    const response = await api.get('/historico/extrato-bancario', { params })
    return response.data.data ?? response.data
  },
}
