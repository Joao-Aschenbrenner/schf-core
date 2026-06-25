import { useQuery } from '@tanstack/react-query'
import { auditTrailService } from '../services/auditTrail'

export function useAuditTrail(params?: Record<string, any>) {
  return useQuery({
    queryKey: ['audit-trail', params],
    queryFn: () => auditTrailService.list(params),
  })
}

export function useModelTimeline(modelType: string, modelId: number) {
  return useQuery({
    queryKey: ['audit-trail', modelType, modelId],
    queryFn: () => auditTrailService.modelTimeline(modelType, modelId),
    enabled: !!modelType && !!modelId,
  })
}
