import axios from 'axios'
import { useConfigStore } from '../stores/configStore'
import { useMasterAuthStore } from '../stores/masterAuthStore'

const adminApi = axios.create({
  baseURL: '/api/admin',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

adminApi.interceptors.request.use((config) => {
  const { apiUrl } = useConfigStore.getState()
  const { token, isSessionExpired } = useMasterAuthStore.getState()

  config.baseURL = `${apiUrl.replace('/api', '')}/api/admin`

  if (token && !isSessionExpired()) {
    config.headers.Authorization = `Bearer ${token}`
  } else if (token && isSessionExpired()) {
    useMasterAuthStore.getState().clearAuth()
  }

  return config
})

adminApi.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      useMasterAuthStore.getState().clearAuth()
    }
    return Promise.reject(error)
  }
)

export { adminApi }

export const masterAuthService = {
  async login(email: string, password: string) {
    const { apiUrl } = useConfigStore.getState()
    await axios.get(`${apiUrl}/sanctum/csrf-cookie`, { withCredentials: true })

    const response = await axios.post(`${apiUrl}/admin/auth/master-login`, { email, password }, {
      withCredentials: true,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })

    const { user, token, expires_at } = response.data
    const expiresIn = expires_at ? Math.floor((new Date(expires_at).getTime() - Date.now()) / 1000) : 4 * 60 * 60
    useMasterAuthStore.getState().setToken(token, user, expiresIn)
    return response.data
  },

  async logout() {
    try {
      await adminApi.post('/auth/master-logout')
    } finally {
      useMasterAuthStore.getState().clearAuth()
    }
  },
}