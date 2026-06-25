import { api } from './api'
import type { Payable, PaginatedResponse } from '../types'

export const payableService = {
  async list(params?: Record<string, any>): Promise<PaginatedResponse<Payable>> {
    const response = await api.get('/payables', { params })
    return response.data
  },

  async get(id: number): Promise<Payable> {
    const response = await api.get(`/payables/${id}`)
    return response.data.data
  },

  async create(data: Partial<Payable>): Promise<Payable> {
    const response = await api.post('/payables', data)
    return response.data.data
  },

  async update(id: number, data: Partial<Payable>): Promise<Payable> {
    const response = await api.put(`/payables/${id}`, data)
    return response.data.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/payables/${id}`)
  },

  async approve(id: number): Promise<Payable> {
    const response = await api.post(`/payables/${id}/approve`)
    return response.data.data
  },

  async pay(id: number, data: {
    paid_amount?: number
    discount?: number
    interest?: number
    payment_date?: string
    payment_method?: string
    receipt_number?: string
  }): Promise<Payable> {
    const response = await api.post(`/payables/${id}/pay`, data)
    return response.data.data
  },

  async agingReport(): Promise<Record<string, { count: number; total: number }>> {
    const response = await api.get('/payables/aging-report')
    return response.data.data
  },
}
