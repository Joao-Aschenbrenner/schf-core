import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { provisionService } from '../services/provisionApi'

export function useProvisions(params?: Record<string, any>) {
  return useQuery({
    queryKey: ['provisions', params],
    queryFn: () => provisionService.list(params),
  })
}

export function useProvision(id: number) {
  return useQuery({
    queryKey: ['provisions', id],
    queryFn: () => provisionService.get(id),
    enabled: !!id,
  })
}

export function useCreateProvision() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: provisionService.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['provisions'] })
    },
  })
}

export function useUpdateProvision() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      provisionService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['provisions'] })
    },
  })
}

export function useDeleteProvision() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: provisionService.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['provisions'] })
    },
  })
}

export function useConfirmProvision() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: provisionService.confirm,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['provisions'] })
    },
  })
}

export function usePayProvision() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      provisionService.pay(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['provisions'] })
    },
  })
}

export function useCancelProvision() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data?: any }) =>
      provisionService.cancel(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['provisions'] })
    },
  })
}
