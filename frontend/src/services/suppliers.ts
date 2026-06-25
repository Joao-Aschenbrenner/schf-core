import { api } from './api'
import type { Supplier, PaginatedResponse } from '../types'

export const supplierService = {
  async list(params?: Record<string, any>): Promise<PaginatedResponse<Supplier>> {
    const response = await api.get('/suppliers', { params })
    return response.data
  },

  async get(id: number): Promise<Supplier> {
    const response = await api.get(`/suppliers/${id}`)
    return response.data.data
  },

  async create(data: Partial<Supplier>): Promise<Supplier> {
    const response = await api.post('/suppliers', data)
    return response.data.data
  },

  async update(id: number, data: Partial<Supplier>): Promise<Supplier> {
    const response = await api.put(`/suppliers/${id}`, data)
    return response.data.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/suppliers/${id}`)
  },

  async financialSummary(id: number): Promise<any> {
    const response = await api.get(`/suppliers/${id}/financial-summary`)
    return response.data.data
  },
}
