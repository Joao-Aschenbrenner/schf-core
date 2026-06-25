import { create } from 'zustand'
import { persist, createJSONStorage } from 'zustand/middleware'

interface MasterAuthState {
  token: string | null
  isAuthenticated: boolean
  expiresAt: number | null
  user: { id: number; name: string; email: string; is_master: boolean } | null
}

interface MasterAuthStore extends MasterAuthState {
  setToken: (token: string, user: MasterAuthState['user'], expiresIn?: number) => void
  clearAuth: () => void
  isSessionExpired: () => boolean
}

const SESSION_DURATION_MS = 4 * 60 * 60 * 1000

export const useMasterAuthStore = create<MasterAuthStore>()(
  persist(
    (set, get) => ({
      token: null,
      isAuthenticated: false,
      expiresAt: null,
      user: null,

      setToken: (token, user, expiresIn) => {
        const expiresAt = expiresIn
          ? Date.now() + expiresIn * 1000
          : Date.now() + SESSION_DURATION_MS

        set({ token, user, isAuthenticated: true, expiresAt })
      },

      clearAuth: () => {
        set({ token: null, user: null, isAuthenticated: false, expiresAt: null })
      },

      isSessionExpired: () => {
        const { expiresAt } = get()
        if (!expiresAt) return true
        return Date.now() > expiresAt
      },
    }),
    {
      name: 'schf-master-auth',
      storage: createJSONStorage(() => localStorage),
      partialize: (state) => ({
        token: state.token,
        isAuthenticated: state.isAuthenticated,
        expiresAt: state.expiresAt,
        user: state.user,
      }),
    }
  )
)

