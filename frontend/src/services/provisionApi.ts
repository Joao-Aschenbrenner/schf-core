import { api } from './api'
import type { Provision, PaginatedResponse } from '../types'

export const provisionService = {
  async list(params?: Record<string, any>): Promise<PaginatedResponse<Provision>> {
    const response = await api.get('/provisions', { params })
    return response.data
  },

  async get(id: number): Promise<Provision> {
    const response = await api.get(`/provisions/${id}`)
    return response.data.data
  },

  async create(data: Partial<Provision>): Promise<Provision> {
    const response = await api.post('/provisions', data)
    return response.data.data
  },

  async update(id: number, data: Partial<Provision>): Promise<Provision> {
    const response = await api.put(`/provisions/${id}`, data)
    return response.data.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/provisions/${id}`)
  },

  async confirm(id: number): Promise<Provision> {
    const response = await api.post(`/provisions/${id}/confirm`)
    return response.data.data
  },

  async pay(id: number, data: { payment_date?: string; payment_method?: string }): Promise<Provision> {
    const response = await api.post(`/provisions/${id}/pay`, data)
    return response.data.data
  },

  async cancel(id: number, data?: { reason?: string }): Promise<Provision> {
    const response = await api.post(`/provisions/${id}/cancel`, data)
    return response.data.data
  },
}
