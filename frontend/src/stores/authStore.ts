import { create } from 'zustand'
import { persist, createJSONStorage } from 'zustand/middleware'

interface AuthState {
  token: string | null
  isAuthenticated: boolean
  expiresAt: number | null
}

interface AuthStore extends AuthState {
  setToken: (token: string, expiresIn?: number) => void
  clearAuth: () => void
  isSessionExpired: () => boolean
}

const SESSION_DURATION_MS = 12 * 60 * 60 * 1000

export const useAuthStore = create<AuthStore>()(
  persist(
    (set, get) => ({
      token: null,
      isAuthenticated: false,
      expiresAt: null,

      setToken: (token, expiresIn) => {
        const expiresAt = expiresIn
          ? Date.now() + expiresIn * 1000
          : Date.now() + SESSION_DURATION_MS

        set({ token, isAuthenticated: true, expiresAt })
      },

      clearAuth: () => {
        set({ token: null, isAuthenticated: false, expiresAt: null })
      },

      isSessionExpired: () => {
        const { expiresAt } = get()
        if (!expiresAt) return true
        return Date.now() > expiresAt
      },
    }),
    {
      name: 'schf-auth',
      storage: createJSONStorage(() => localStorage),
      partialize: (state) => ({
        token: state.token,
        isAuthenticated: state.isAuthenticated,
        expiresAt: state.expiresAt,
      }),
    }
  )
)


