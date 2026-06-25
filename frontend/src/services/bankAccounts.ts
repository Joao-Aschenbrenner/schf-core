import { api } from './api'
import type { BankAccount, PaginatedResponse } from '../types'

export const bankAccountService = {
  async list(params?: Record<string, any>): Promise<PaginatedResponse<BankAccount>> {
    const response = await api.get('/bank-accounts', { params })
    return response.data
  },

  async get(id: number): Promise<BankAccount> {
    const response = await api.get(`/bank-accounts/${id}`)
    return response.data.data
  },

  async create(data: Partial<BankAccount>): Promise<BankAccount> {
    const response = await api.post('/bank-accounts', data)
    return response.data.data
  },

  async update(id: number, data: Partial<BankAccount>): Promise<BankAccount> {
    const response = await api.put(`/bank-accounts/${id}`, data)
    return response.data.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/bank-accounts/${id}`)
  },
}
