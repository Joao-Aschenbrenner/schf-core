import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { preLaunchService } from '../services/preLaunches'

export function usePreLaunches(params?: Record<string, any>) {
  return useQuery({
    queryKey: ['pre-launches', params],
    queryFn: () => preLaunchService.list(params),
  })
}

export function usePreLaunch(id: number) {
  return useQuery({
    queryKey: ['pre-launches', id],
    queryFn: () => preLaunchService.get(id),
    enabled: !!id,
  })
}

export function useCreatePreLaunch() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: preLaunchService.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['pre-launches'] })
    },
  })
}

export function useUpdatePreLaunch() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      preLaunchService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['pre-launches'] })
    },
  })
}

export function useConfirmPreLaunch() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: preLaunchService.confirm,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['pre-launches'] })
    },
  })
}

export function useCancelPreLaunch() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: preLaunchService.cancel,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['pre-launches'] })
    },
  })
}
