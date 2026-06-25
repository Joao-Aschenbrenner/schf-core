import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { conciliationService } from '../services/conciliation'

export function useStatements(params?: Record<string, any>) {
  return useQuery({
    queryKey: ['conciliation', 'statements', params],
    queryFn: () => conciliationService.listStatements(params),
  })
}

export function useStatement(id: number) {
  return useQuery({
    queryKey: ['conciliation', 'statements', id],
    queryFn: () => conciliationService.getStatement(id),
    enabled: !!id,
  })
}

export function useImportOfx() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ bankAccountId, ofxContent }: { bankAccountId: number; ofxContent: string }) =>
      conciliationService.importOfx(bankAccountId, ofxContent),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['conciliation'] })
    },
  })
}

export function useConciliate() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ statementItemId, payableId }: { statementItemId: number; payableId: number }) =>
      conciliationService.conciliate(statementItemId, payableId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['conciliation'] })
      queryClient.invalidateQueries({ queryKey: ['payables'] })
    },
  })
}

export function useUnmatch() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: conciliationService.unmatch,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['conciliation'] })
      queryClient.invalidateQueries({ queryKey: ['payables'] })
    },
  })
}

export function useAutoMatch() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: conciliationService.autoMatch,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['conciliation'] })
      queryClient.invalidateQueries({ queryKey: ['payables'] })
    },
  })
}
