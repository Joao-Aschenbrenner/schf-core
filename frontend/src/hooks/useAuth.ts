import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { authService } from '../services/auth'

export function useAuth() {
  const queryClient = useQueryClient()

  const user = useQuery({
    queryKey: ['auth', 'me'],
    queryFn: () => authService.me(),
    retry: false,
    staleTime: 5 * 60 * 1000,
  })

  const login = useMutation({
    mutationFn: ({ email, password }: { email: string; password: string }) =>
      authService.login(email, password),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['auth', 'me'] })
    },
  })

  const logout = useMutation({
    mutationFn: () => authService.logout(),
    onSuccess: () => {
      queryClient.clear()
    },
  })

  return { user, login, logout }
}
