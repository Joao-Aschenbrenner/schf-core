import { api } from './api'
import type { Receivable, PaginatedResponse } from '../types'

export const receivableService = {
  async list(params?: Record<string, any>): Promise<PaginatedResponse<Receivable>> {
    const response = await api.get('/receivables', { params })
    return response.data
  },

  async get(id: number): Promise<Receivable> {
    const response = await api.get(`/receivables/${id}`)
    return response.data.data
  },

  async create(data: Partial<Receivable>): Promise<Receivable> {
    const response = await api.post('/receivables', data)
    return response.data.data
  },

  async update(id: number, data: Partial<Receivable>): Promise<Receivable> {
    const response = await api.put(`/receivables/${id}`, data)
    return response.data.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/receivables/${id}`)
  },

  async approve(id: number): Promise<Receivable> {
    const response = await api.post(`/receivables/${id}/approve`)
    return response.data.data
  },

  async receive(id: number, data: {
    received_amount?: number
    received_date?: string
    payment_method?: string
  }): Promise<Receivable> {
    const response = await api.post(`/receivables/${id}/receive`, data)
    return response.data.data
  },
}
