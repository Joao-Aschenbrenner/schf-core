import { Navigate, NavLink, Outlet } from 'react-router-dom'
import { useMasterAuthStore } from '../stores/masterAuthStore'
import { masterAuthService } from '../services/adminApi'
import {
  LayoutDashboard,
  Users,
  Shield,
  ScrollText,
  Database,
  Container,
  Wrench,
  CheckCircle,
  Download,
  LogOut,
  RefreshCw,
} from 'lucide-react'

const adminNavItems = [
  { label: 'Dashboard', path: '/admin', icon: LayoutDashboard },
  { label: 'Saúde do Sistema', path: '/admin/system-health', icon: CheckCircle },
  { label: 'Usuários', path: '/admin/users', icon: Users },
  { label: 'Permissões', path: '/admin/permissions', icon: Shield },
  { label: 'Logs', path: '/admin/logs', icon: ScrollText },
  { label: 'Backups', path: '/admin/backups', icon: Database },
  { label: 'Infraestrutura', path: '/admin/containers', icon: Container },
  { label: 'Manutenção', path: '/admin/maintenance', icon: Wrench },
  { label: 'Integridade', path: '/admin/integrity', icon: CheckCircle },
  { label: 'Exportar', path: '/admin/export', icon: Download },
  { label: 'Atualizações', path: '/admin/updates', icon: RefreshCw },
]

function MasterAuthGuard({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, isSessionExpired, user } = useMasterAuthStore()

  if (!isAuthenticated || isSessionExpired()) {
    return <Navigate to="/login" replace />
  }

  if (!user?.is_master) {
    return <Navigate to="/login" replace />
  }

  return <>{children}</>
}

export function AdminLayout() {
  const { user, clearAuth } = useMasterAuthStore()

  const handleLogout = async () => {
    await masterAuthService.logout()
    clearAuth()
  }

  return (
    <MasterAuthGuard>
      <div className="flex h-screen bg-muted/30">
        <aside className="w-64 bg-card border-r flex flex-col">
          <div className="p-4 border-b">
            <h1 className="text-lg font-bold text-primary flex items-center gap-2">
              <span>⚙️</span> Admin Panel
            </h1>
            <p className="text-xs text-muted-foreground mt-0.5">
              {user?.name || 'MASTER'}
            </p>
          </div>

          <nav className="flex-1 overflow-y-auto py-2">
            {adminNavItems.map((item) => (
              <NavLink
                key={item.path}
                to={item.path}
                end={item.path === '/admin'}
                className={({ isActive }) =>
                  `flex items-center gap-3 px-4 py-2.5 text-sm transition-colors ${
                    isActive
                      ? 'bg-primary/10 text-primary font-medium border-r-2 border-primary'
                      : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                  }`
                }
              >
                <item.icon className="w-4 h-4" />
                <span>{item.label}</span>
              </NavLink>
            ))}
          </nav>

          <div className="p-4 border-t">
            <div className="flex items-center justify-between">
              <span className="text-xs text-muted-foreground">
                {user?.email}
              </span>
              <button
                onClick={handleLogout}
                className="p-1.5 rounded-md hover:bg-destructive/10 text-muted-foreground hover:text-destructive transition-colors"
                title="Sair do painel master"
              >
                <LogOut className="w-4 h-4" />
              </button>
            </div>
          </div>
        </aside>

        <main className="flex-1 overflow-y-auto">
          <Outlet />
        </main>
      </div>
    </MasterAuthGuard>
  )
}