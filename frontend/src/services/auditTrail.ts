import { api } from './api'

export interface AuditTrailEntry {
  id: number
  model_type: string
  model_id: number
  user_id: number | null
  action: string
  properties: Record<string, any>
  created_at: string
  user?: { id: number; name: string; email: string }
}

export interface AuditTrailTimeline {
  model_type: string
  model_id: number
  entries: AuditTrailEntry[]
}

export const auditTrailService = {
  async list(params?: Record<string, any>): Promise<any> {
    const response = await api.get('/audit-trail', { params })
    return response.data
  },

  async modelTimeline(modelType: string, modelId: number): Promise<AuditTrailTimeline> {
    const response = await api.get(`/audit-trail/${modelType}/${modelId}`)
    return response.data.data
  },
}
