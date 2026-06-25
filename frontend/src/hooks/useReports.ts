import { useQuery } from '@tanstack/react-query'
import { reportService } from '../services/reports'

export function useSupplierReport(params?: Record<string, any>) {
  return useQuery({
    queryKey: ['reports', 'suppliers', params],
    queryFn: () => reportService.supplierReport(params),
  })
}

export function useCategoryReport(params?: Record<string, any>) {
  return useQuery({
    queryKey: ['reports', 'categories', params],
    queryFn: () => reportService.categoryReport(params),
  })
}

export function usePlanReport(params?: Record<string, any>) {
  return useQuery({
    queryKey: ['reports', 'plans', params],
    queryFn: () => reportService.planReport(params),
  })
}

export function useCashFlowReport(params?: Record<string, any>) {
  return useQuery({
    queryKey: ['reports', 'cash-flow', params],
    queryFn: () => reportService.cashFlowReport(params),
    enabled: !!params?.date_from && !!params?.date_to,
  })
}

export function usePrestacaoContas(params?: Record<string, any>) {
  return useQuery({
    queryKey: ['reports', 'prestacao-contas', params],
    queryFn: () => reportService.prestacaoContas(params),
  })
}
