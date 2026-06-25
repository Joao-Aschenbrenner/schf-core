import { api } from './api'
import { useAuthStore } from '../stores/authStore'
import type { User } from '../types'

export const authService = {
  async login(email: string, password: string) {
    await api.get('/sanctum/csrf-cookie')
    const response = await api.post('/auth/login', { email, password })
    const { token } = response.data

    useAuthStore.getState().setToken(token)

    return response.data
  },

  async logout() {
    try {
      await api.post('/auth/logout')
    } finally {
      useAuthStore.getState().clearAuth()
    }
  },

  async me(): Promise<User> {
    const response = await api.get('/me')
    return response.data.data
  },
}
