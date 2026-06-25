import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { payableService } from '../services/payables'

export function usePayables(params?: Record<string, any>) {
  return useQuery({
    queryKey: ['payables', params],
    queryFn: () => payableService.list(params),
  })
}

export function usePayable(id: number) {
  return useQuery({
    queryKey: ['payables', id],
    queryFn: () => payableService.get(id),
    enabled: !!id,
  })
}

export function useCreatePayable() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: payableService.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['payables'] })
    },
  })
}

export function useUpdatePayable() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      payableService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['payables'] })
    },
  })
}

export function useDeletePayable() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: payableService.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['payables'] })
    },
  })
}

export function useApprovePayable() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: payableService.approve,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['payables'] })
    },
  })
}

export function usePayPayable() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      payableService.pay(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['payables'] })
    },
  })
}

export function useAgingReport() {
  return useQuery({
    queryKey: ['payables', 'aging-report'],
    queryFn: () => payableService.agingReport(),
  })
}
