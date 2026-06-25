import { api } from './api'
import type { HealthPlan, PaginatedResponse } from '../types'

export const healthPlanService = {
  async list(params?: Record<string, any>): Promise<PaginatedResponse<HealthPlan>> {
    const response = await api.get('/health-plans', { params })
    return response.data
  },

  async get(id: number): Promise<HealthPlan> {
    const response = await api.get(`/health-plans/${id}`)
    return response.data.data
  },

  async create(data: Partial<HealthPlan>): Promise<HealthPlan> {
    const response = await api.post('/health-plans', data)
    return response.data.data
  },

  async update(id: number, data: Partial<HealthPlan>): Promise<HealthPlan> {
    const response = await api.put(`/health-plans/${id}`, data)
    return response.data.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/health-plans/${id}`)
  },

  async addResourcePlan(id: number, data: any): Promise<any> {
    const response = await api.post(`/health-plans/${id}/resource-plans`, data)
    return response.data.data
  },

  async balance(id: number): Promise<any> {
    const response = await api.get(`/health-plans/${id}/balance`)
    return response.data.data
  },
}
