import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { receivableService } from '../services/receivableApi'

export function useReceivables(params?: Record<string, any>) {
  return useQuery({
    queryKey: ['receivables', params],
    queryFn: () => receivableService.list(params),
  })
}

export function useReceivable(id: number) {
  return useQuery({
    queryKey: ['receivables', id],
    queryFn: () => receivableService.get(id),
    enabled: !!id,
  })
}

export function useCreateReceivable() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: receivableService.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['receivables'] })
    },
  })
}

export function useUpdateReceivable() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      receivableService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['receivables'] })
    },
  })
}

export function useDeleteReceivable() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: receivableService.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['receivables'] })
    },
  })
}

export function useApproveReceivable() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: receivableService.approve,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['receivables'] })
    },
  })
}

export function useReceiveReceivable() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      receivableService.receive(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['receivables'] })
    },
  })
}
