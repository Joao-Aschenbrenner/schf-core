import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { supplierService } from '../services/suppliers'

export function useSuppliers(params?: Record<string, any>) {
  return useQuery({
    queryKey: ['suppliers', params],
    queryFn: () => supplierService.list(params),
  })
}

export function useSupplier(id: number) {
  return useQuery({
    queryKey: ['suppliers', id],
    queryFn: () => supplierService.get(id),
    enabled: !!id,
  })
}

export function useCreateSupplier() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: supplierService.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['suppliers'] })
    },
  })
}

export function useUpdateSupplier() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      supplierService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['suppliers'] })
    },
  })
}

export function useDeleteSupplier() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: supplierService.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['suppliers'] })
    },
  })
}

export function useSupplierFinancialSummary(id: number) {
  return useQuery({
    queryKey: ['suppliers', id, 'financial-summary'],
    queryFn: () => supplierService.financialSummary(id),
    enabled: !!id,
  })
}
