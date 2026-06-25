import axios from 'axios'
import { useConfigStore } from '../stores/configStore'
import { useAuthStore } from '../stores/authStore'

const api = axios.create({
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const { apiUrl } = useConfigStore.getState()
  const { token, isSessionExpired } = useAuthStore.getState()

  config.baseURL = apiUrl

  if (token && !isSessionExpired()) {
    config.headers.Authorization = `Bearer ${token}`
  } else if (token && isSessionExpired()) {
    useAuthStore.getState().clearAuth()
  }

  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      useAuthStore.getState().clearAuth()
    }

    if (!error.response) {
      useConfigStore.getState().setBackendStatus('offline')
    }

    return Promise.reject(error)
  },
)

export { api }

export async function healthCheck(): Promise<boolean> {
  const { apiUrl } = useConfigStore.getState()
  const { setBackendStatus, setLastHealthCheck } = useConfigStore.getState()

  setBackendStatus('checking')

  try {
    const response = await axios.get(`${apiUrl}/health`, { timeout: 5000 })
    const now = new Date().toISOString()

    if (response.data?.status === 'ok') {
      setBackendStatus('online')
      setLastHealthCheck(now)
      return true
    }

    setBackendStatus('offline')
    return false
  } catch {
    setBackendStatus('offline')
    setLastHealthCheck(new Date().toISOString())
    return false
  }
}
