import { api } from './api'
import type { CashRegister, CashMovement, PaginatedResponse } from '../types'

export const cashRegisterService = {
  async list(params?: Record<string, any>): Promise<PaginatedResponse<CashRegister>> {
    const response = await api.get('/operacional/cash-registers', { params })
    return response.data
  },

  async get(id: number): Promise<CashRegister> {
    const response = await api.get(`/operacional/cash-registers/${id}`)
    return response.data.data
  },

  async getOpen(): Promise<CashRegister | null> {
    const response = await api.get('/operacional/cash-registers/open')
    return response.data.data
  },

  async open(data: { date: string; opening_balance: number }): Promise<CashRegister> {
    const response = await api.post('/operacional/cash-registers', data)
    return response.data.data
  },

  async close(id: number, data?: { closing_balance?: number }): Promise<CashRegister> {
    const response = await api.put(`/operacional/cash-registers/${id}/close`, data)
    return response.data.data
  },
}

export const cashMovementService = {
  async list(params?: Record<string, any>): Promise<PaginatedResponse<CashMovement>> {
    const response = await api.get('/operacional/cash-movements', { params })
    return response.data
  },

  async create(data: {
    cash_register_id: number
    type: 'credit' | 'debit'
    amount: number
    description: string
    category?: string
    payment_method?: string
    document?: string
  }): Promise<CashMovement> {
    const response = await api.post('/operacional/cash-movements', data)
    return response.data.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/operacional/cash-movements/${id}`)
  },
}
