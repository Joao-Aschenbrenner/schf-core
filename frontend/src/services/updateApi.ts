import adminApi from './adminApi'

export interface UpdateCheck {
  current_version: string
  latest_version: string | null
  update_available: boolean
  release_notes: string
  published_at: string | null
  html_url: string | null
}

export interface UpdateResult {
  success: boolean
  version?: string
  duration?: number
  message: string
}

export interface ChangelogResponse {
  current_version: string
  changelog: string
}

export const updateApi = {
  check: async (): Promise<UpdateCheck> => {
    const response = await adminApi.get('/admin/updates/check')
    return response.data
  },

  run: async (version?: string): Promise<UpdateResult> => {
    const response = await adminApi.post('/admin/updates/run', { version })
    return response.data
  },

  rollback: async (): Promise<UpdateResult> => {
    const response = await adminApi.post('/admin/updates/rollback')
    return response.data
  },

  changelog: async (): Promise<ChangelogResponse> => {
    const response = await adminApi.get('/admin/updates/changelog')
    return response.data
  },
}