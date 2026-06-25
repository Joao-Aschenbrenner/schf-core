import { Outlet, Navigate, Link, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import { useConfigStore } from '../stores/configStore'
import { useAuthStore } from '../stores/authStore'
import { cn } from '../utils/cn'
import { Button } from '../components/ui/Button'
import { Badge } from '../components/ui/Badge'

const navItems = [
  { label: 'Dashboard', path: '/' },
  { label: 'Fornecedores', path: '/fornecedores' },
  { label: 'Convênios', path: '/convenios' },
  { label: 'Contas Bancárias', path: '/contas-bancarias' },
  { label: 'Categorias', path: '/categorias-despesa' },
  { label: 'NF-e', path: '/nfes' },
  { label: 'Contas a Pagar', path: '/contas-a-pagar' },
  { label: 'Pré-Lançamentos', path: '/pre-lancamentos' },
  { label: 'Conciliação', path: '/conciliacao' },
  { label: 'Cronograma', path: '/cronograma' },
  { label: 'Relatórios', path: '/relatorios' },
  { label: 'Auditoria', path: '/auditoria' },
]

export function DashboardLayout() {
  const { user } = useAuth()
  const location = useLocation()
  const navigate = useNavigate()
  const { backendStatus, serverMode } = useConfigStore()
  const { clearAuth, isSessionExpired } = useAuthStore()

  if (user.isError || isSessionExpired()) {
    clearAuth()
    return <Navigate to="/login" replace />
  }

  if (user.isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary" />
      </div>
    )
  }

  const handleLogout = () => {
    clearAuth()
    navigate('/login')
  }

  const statusVariant = backendStatus === 'online' ? 'success'
    : backendStatus === 'checking' ? 'warning'
    : 'destructive'

  return (
    <div className="min-h-screen bg-background flex">
      <aside className="w-64 border-r bg-card p-4 flex flex-col">
        <div className="mb-6">
          <h1 className="text-lg font-bold text-primary">SCHF</h1>
          <p className="text-xs text-muted-foreground">Sistema Financeiro</p>
          <div className="mt-2 flex items-center gap-2">
            <Badge variant={statusVariant} className="text-[10px]">
              {backendStatus === 'online' ? 'Online' : backendStatus === 'checking' ? 'Verificando' : 'Offline'}
            </Badge>
          </div>
        </div>

        <nav className="space-y-1 flex-1">
          {navItems.map((item) => (
            <Link
              key={item.path}
              to={item.path}
              className={cn(
                'block rounded-md px-3 py-2 text-sm font-medium transition-colors',
                location.pathname === item.path
                  ? 'bg-primary text-primary-foreground'
                  : 'text-muted-foreground hover:bg-muted hover:text-foreground'
              )}
            >
              {item.label}
            </Link>
          ))}
        </nav>

        <div className="border-t pt-4 mt-4 space-y-3">
          <Link
            to="/conexao"
            className="block text-xs text-muted-foreground hover:text-foreground"
          >
            Configurar Conexão
          </Link>
          <Link
            to="/tunel"
            className={`block rounded-md px-3 py-2 text-sm font-medium transition-colors ${
              location.pathname === '/tunel'
                ? 'bg-primary text-primary-foreground'
                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
            }`}
          >
            🌐 Túnel Externo
          </Link>
          {serverMode === 'server' && (
            <Link
              to="/servidor"
              className={`block rounded-md px-3 py-2 text-sm font-medium transition-colors ${
                location.pathname === '/servidor'
                  ? 'bg-primary text-primary-foreground'
                  : 'text-muted-foreground hover:bg-muted hover:text-foreground'
              }`}
            >
              Painel do Servidor
            </Link>
          )}
          <p className="text-sm font-medium text-foreground truncate">
            {user.data?.name || 'Usuário'}
          </p>
          <p className="text-xs text-muted-foreground">{user.data?.email}</p>
          <Button variant="ghost" size="sm" onClick={handleLogout} className="w-full justify-start text-muted-foreground">
            Sair
          </Button>
        </div>
      </aside>

      <main className="flex-1 p-6 overflow-auto">
        <Outlet />
      </main>
    </div>
  )
}
