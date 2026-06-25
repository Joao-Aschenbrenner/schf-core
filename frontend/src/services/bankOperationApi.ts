import { api } from './api'
import type { BankOperation, PaginatedResponse } from '../types'

export const bankOperationService = {
  async list(params?: Record<string, any>): Promise<PaginatedResponse<BankOperation>> {
    const response = await api.get('/bank-operations', { params })
    return response.data
  },

  async get(id: number): Promise<BankOperation> {
    const response = await api.get(`/bank-operations/${id}`)
    return response.data.data
  },

  async create(data: Partial<BankOperation>): Promise<BankOperation> {
    const response = await api.post('/bank-operations', data)
    return response.data.data
  },

  async update(id: number, data: Partial<BankOperation>): Promise<BankOperation> {
    const response = await api.put(`/bank-operations/${id}`, data)
    return response.data.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/bank-operations/${id}`)
  },
}
