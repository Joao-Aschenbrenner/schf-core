import { useQuery } from '@tanstack/react-query'
import { cronogramaService } from '../services/cronograma'

export function useCronograma(params?: Record<string, any>) {
  return useQuery({
    queryKey: ['cronograma', params],
    queryFn: () => cronogramaService.get(params),
  })
}
