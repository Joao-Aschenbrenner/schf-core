import api from './api'

export interface SetupStatus {
  is_configured: boolean
  organization: {
    id: number
    name: string
    cnpj: string | null
    city: string | null
    state: string | null
    email: string | null
    phone: string | null
    address: string | null
  } | null
}

export interface OrganizationData {
  name: string
  cnpj?: string
  city?: string
  state?: string
  email?: string
  phone?: string
  address?: string
}

export interface AdminData {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export const setupApi = {
  getStatus: async (): Promise<SetupStatus> => {
    const response = await api.get('/setup/status')
    return response.data
  },

  createOrganization: async (data: OrganizationData) => {
    const response = await api.post('/setup/organization', data)
    return response.data
  },

  createAdmin: async (data: AdminData) => {
    const response = await api.post('/setup/admin', data)
    return response.data
  },

  complete: async () => {
    const response = await api.post('/setup/complete')
    return response.data
  },
}