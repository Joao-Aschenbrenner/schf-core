import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { cashRegisterService, cashMovementService } from '../services/cashRegisterApi'

export function useCashRegisters(params?: Record<string, any>) {
  return useQuery({
    queryKey: ['cash-registers', params],
    queryFn: () => cashRegisterService.list(params),
  })
}

export function useCashRegister(id: number) {
  return useQuery({
    queryKey: ['cash-registers', id],
    queryFn: () => cashRegisterService.get(id),
    enabled: !!id,
  })
}

export function useOpenCashRegister() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: cashRegisterService.open,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cash-registers'] })
    },
  })
}

export function useCloseCashRegister() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data?: any }) =>
      cashRegisterService.close(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cash-registers'] })
    },
  })
}

export function useOpenCashRegisterData() {
  return useQuery({
    queryKey: ['cash-registers', 'open'],
    queryFn: cashRegisterService.getOpen,
  })
}

export function useCashMovements(params?: Record<string, any>) {
  return useQuery({
    queryKey: ['cash-movements', params],
    queryFn: () => cashMovementService.list(params),
  })
}

export function useCreateCashMovement() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: cashMovementService.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cash-movements'] })
      queryClient.invalidateQueries({ queryKey: ['cash-registers'] })
    },
  })
}
