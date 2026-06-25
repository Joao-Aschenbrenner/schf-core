import { api } from './api'
import type { Nfe, PaginatedResponse } from '../types'

export const nfeService = {
  async list(params?: Record<string, any>): Promise<PaginatedResponse<Nfe>> {
    const response = await api.get('/nfes', { params })
    return response.data
  },

  async get(id: number): Promise<Nfe> {
    const response = await api.get(`/nfes/${id}`)
    return response.data.data
  },

  async create(data: Partial<Nfe>): Promise<Nfe> {
    const response = await api.post('/nfes', data)
    return response.data.data
  },

  async update(id: number, data: Partial<Nfe>): Promise<Nfe> {
    const response = await api.put(`/nfes/${id}`, data)
    return response.data.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/nfes/${id}`)
  },

  async confirm(id: number): Promise<Nfe> {
    const response = await api.post(`/nfes/${id}/confirm`)
    return response.data.data
  },

  async generatePayable(id: number, data: any): Promise<any> {
    const response = await api.post(`/nfes/${id}/generate-payable`, data)
    return response.data.data
  },
}
