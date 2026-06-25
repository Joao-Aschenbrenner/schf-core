import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { healthPlanService } from '../services/healthPlans'

export function useHealthPlans(params?: Record<string, any>) {
  return useQuery({
    queryKey: ['health-plans', params],
    queryFn: () => healthPlanService.list(params),
  })
}

export function useHealthPlan(id: number) {
  return useQuery({
    queryKey: ['health-plans', id],
    queryFn: () => healthPlanService.get(id),
    enabled: !!id,
  })
}

export function useCreateHealthPlan() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: healthPlanService.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['health-plans'] })
    },
  })
}

export function useUpdateHealthPlan() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      healthPlanService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['health-plans'] })
    },
  })
}

export function useDeleteHealthPlan() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: healthPlanService.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['health-plans'] })
    },
  })
}

export function useHealthPlanBalance(id: number) {
  return useQuery({
    queryKey: ['health-plans', id, 'balance'],
    queryFn: () => healthPlanService.balance(id),
    enabled: !!id,
  })
}
