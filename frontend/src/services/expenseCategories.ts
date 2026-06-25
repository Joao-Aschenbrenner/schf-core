import { api } from './api'
import type { ExpenseCategory } from '../types'

export const expenseCategoryService = {
  async list(params?: Record<string, any>): Promise<ExpenseCategory[]> {
    const response = await api.get('/expense-categories', { params })
    return response.data.data
  },

  async get(id: number): Promise<ExpenseCategory> {
    const response = await api.get(`/expense-categories/${id}`)
    return response.data.data
  },

  async create(data: Partial<ExpenseCategory>): Promise<ExpenseCategory> {
    const response = await api.post('/expense-categories', data)
    return response.data.data
  },

  async update(id: number, data: Partial<ExpenseCategory>): Promise<ExpenseCategory> {
    const response = await api.put(`/expense-categories/${id}`, data)
    return response.data.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/expense-categories/${id}`)
  },
}
