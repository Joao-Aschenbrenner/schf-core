import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { nfeService } from '../services/nfes'

export function useNfes(params?: Record<string, any>) {
  return useQuery({
    queryKey: ['nfes', params],
    queryFn: () => nfeService.list(params),
  })
}

export function useNfe(id: number) {
  return useQuery({
    queryKey: ['nfes', id],
    queryFn: () => nfeService.get(id),
    enabled: !!id,
  })
}

export function useCreateNfe() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: nfeService.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['nfes'] })
    },
  })
}

export function useUpdateNfe() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      nfeService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['nfes'] })
    },
  })
}

export function useDeleteNfe() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: nfeService.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['nfes'] })
    },
  })
}

export function useConfirmNfe() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: nfeService.confirm,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['nfes'] })
    },
  })
}

export function useGenerateNfePayable() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      nfeService.generatePayable(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['nfes'] })
      queryClient.invalidateQueries({ queryKey: ['payables'] })
    },
  })
}
