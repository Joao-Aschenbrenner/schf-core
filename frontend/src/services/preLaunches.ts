import { api } from './api'
import type { PreLaunch, PaginatedResponse } from '../types'

export const preLaunchService = {
  async list(params?: Record<string, any>): Promise<PaginatedResponse<PreLaunch>> {
    const response = await api.get('/pre-launches', { params })
    return response.data
  },

  async get(id: number): Promise<PreLaunch> {
    const response = await api.get(`/pre-launches/${id}`)
    return response.data.data
  },

  async create(data: Partial<PreLaunch>): Promise<PreLaunch> {
    const response = await api.post('/pre-launches', data)
    return response.data.data
  },

  async update(id: number, data: Partial<PreLaunch>): Promise<PreLaunch> {
    const response = await api.put(`/pre-launches/${id}`, data)
    return response.data.data
  },

  async confirm(id: number): Promise<PreLaunch> {
    const response = await api.post(`/pre-launches/${id}/confirm`)
    return response.data.data
  },

  async cancel(id: number): Promise<PreLaunch> {
    const response = await api.post(`/pre-launches/${id}/cancel`)
    return response.data.data
  },
}
