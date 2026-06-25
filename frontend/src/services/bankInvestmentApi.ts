import { api } from './api'
import type { BankInvestment, PaginatedResponse } from '../types'

export const bankInvestmentService = {
  async list(params?: Record<string, any>): Promise<PaginatedResponse<BankInvestment>> {
    const response = await api.get('/bank-investments', { params })
    return response.data
  },

  async get(id: number): Promise<BankInvestment> {
    const response = await api.get(`/bank-investments/${id}`)
    return response.data.data
  },

  async create(data: Partial<BankInvestment>): Promise<BankInvestment> {
    const response = await api.post('/bank-investments', data)
    return response.data.data
  },

  async update(id: number, data: Partial<BankInvestment>): Promise<BankInvestment> {
    const response = await api.put(`/bank-investments/${id}`, data)
    return response.data.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/bank-investments/${id}`)
  },

  async redeem(id: number, data: { redeemed_amount: number; redeemed_at: string }): Promise<BankInvestment> {
    const response = await api.post(`/bank-investments/${id}/redeem`, data)
    return response.data.data
  },
}
