import { create } from 'zustand'
import { persist, createJSONStorage } from 'zustand/middleware'
import type { ServerMode } from '../types/tauri'

interface AppConfig {
  apiUrl: string
  environment: 'local' | 'network' | 'vps'
  lastHealthCheck: string | null
  backendStatus: 'online' | 'offline' | 'checking' | 'unknown'
  serverMode: ServerMode
  setupCompleted: boolean
  dockerDir: string
  serverHost: string
}

interface ConfigStore extends AppConfig {
  setApiUrl: (url: string) => void
  setEnvironment: (env: 'local' | 'network' | 'vps') => void
  setBackendStatus: (status: 'online' | 'offline' | 'checking' | 'unknown') => void
  setLastHealthCheck: (date: string | null) => void
  setServerMode: (mode: ServerMode) => void
  setSetupCompleted: (completed: boolean) => void
  setDockerDir: (dir: string) => void
  setServerHost: (host: string) => void
  reset: () => void
}

const initialState: AppConfig = {
  apiUrl: 'http://localhost:9080/api',
  environment: 'local',
  lastHealthCheck: null,
  backendStatus: 'unknown',
  serverMode: 'none',
  setupCompleted: false,
  dockerDir: '',
  serverHost: '',
}

export const useConfigStore = create<ConfigStore>()(
  persist(
    (set) => ({
      ...initialState,
      setApiUrl: (url) => set({ apiUrl: url }),
      setEnvironment: (env) => set({ environment: env }),
      setBackendStatus: (status) => set({ backendStatus: status }),
      setLastHealthCheck: (date) => set({ lastHealthCheck: date }),
      setServerMode: (mode) => set({ serverMode: mode }),
      setSetupCompleted: (completed) => set({ setupCompleted: completed }),
      setDockerDir: (dir) => set({ dockerDir: dir }),
      setServerHost: (host) => set({ serverHost: host }),
      reset: () => set(initialState),
    }),
    {
      name: 'schf-config',
      storage: createJSONStorage(() => localStorage),
      version: 1,
      migrate: (persisted: any, version: number): AppConfig => {
        if (version === 0) {
          return {
            ...initialState,
            ...persisted,
            apiUrl: persisted?.apiUrl?.replace(':8080/api', ':9080/api') ?? initialState.apiUrl,
          }
        }
        return { ...initialState, ...persisted }
      },
    }
  )
)

