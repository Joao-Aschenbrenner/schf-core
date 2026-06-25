import { Routes, Route, Navigate } from 'react-router-dom'
import { AuthLayout } from './layouts/AuthLayout'
import { DashboardLayout } from './layouts/DashboardLayout'
import { AdminLayout } from './layouts/AdminLayout'
import { LoginPage } from './pages/LoginPage'
import { DashboardPage } from './pages/DashboardPage'
import { ConnectionPage } from './pages/ConnectionPage'
import { SetupPage } from './pages/SetupPage'
import { SetupWizardPage } from './pages/SetupWizardPage'
import { ServerDashboardPage } from './pages/ServerDashboardPage'
import { TunnelPage } from './pages/TunnelPage'
import { SuppliersPage } from './pages/suppliers/SuppliersPage'
import { HealthPlansPage } from './pages/healthPlans/HealthPlansPage'
import { BankAccountsPage } from './pages/bankAccounts/BankAccountsPage'
import { ExpenseCategoriesPage } from './pages/expenseCategories/ExpenseCategoriesPage'
import { NfesPage } from './pages/nfes/NfesPage'
import { PayablesPage } from './pages/payables/PayablesPage'
import { PreLaunchesPage } from './pages/preLaunches/PreLaunchesPage'
import { ConciliationPage } from './pages/conciliation/ConciliationPage'
import { AuditTrailPage } from './pages/auditTrail/AuditTrailPage'
import { CronogramaPage } from './pages/cronograma/CronogramaPage'
import { ReportsPage } from './pages/reports/ReportsPage'
import { ExtratoBancarioPage } from './pages/extratoBancario/ExtratoBancarioPage'
import { CaixaInternoPage } from './pages/caixaInterno/CaixaInternoPage'
import { AplicacoesPage } from './pages/aplicacoes/AplicacoesPage'
import { ProvisoesPage } from './pages/provisoes/ProvisoesPage'
import { ReceivablesPage } from './pages/receivables/ReceivablesPage'
import { AdminDashboardPage } from './pages/admin/AdminDashboardPage'
import { UpdatePage } from './pages/admin/UpdatePage'
import { useAuthStore } from './stores/authStore'
import { useConfigStore } from './stores/configStore'

function AuthGuard({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, isSessionExpired } = useAuthStore()
  const { backendStatus } = useConfigStore()

  if (backendStatus === 'offline') {
    return <Navigate to="/conexao" replace />
  }

  if (!isAuthenticated || isSessionExpired()) {
    return <Navigate to="/login" replace />
  }

  return <>{children}</>
}

export function App() {
  const { serverMode } = useConfigStore()

  return (
    <Routes>
      <Route path="/conexao" element={<ConnectionPage />} />
      <Route path="/setup" element={<SetupPage />} />
      <Route path="/setup-wizard" element={<SetupWizardPage />} />
      <Route element={<AuthLayout />}>
        <Route path="/login" element={<LoginPage />} />
      </Route>
      <Route element={
        <AuthGuard>
          <DashboardLayout />
        </AuthGuard>
      }>
        <Route path="/" element={<DashboardPage />} />
        <Route path="/fornecedores" element={<SuppliersPage />} />
        <Route path="/convenios" element={<HealthPlansPage />} />
        <Route path="/contas-bancarias" element={<BankAccountsPage />} />
        <Route path="/categorias-despesa" element={<ExpenseCategoriesPage />} />
        <Route path="/nfes" element={<NfesPage />} />
        <Route path="/contas-a-pagar" element={<PayablesPage />} />
        <Route path="/pre-lancamentos" element={<PreLaunchesPage />} />
        <Route path="/conciliacao" element={<ConciliationPage />} />
        <Route path="/cronograma" element={<CronogramaPage />} />
        <Route path="/relatorios" element={<ReportsPage />} />
        <Route path="/auditoria" element={<AuditTrailPage />} />
        <Route path="/extrato-bancario" element={<ExtratoBancarioPage />} />
        <Route path="/caixa-interno" element={<CaixaInternoPage />} />
        <Route path="/aplicacoes" element={<AplicacoesPage />} />
        <Route path="/provisoes" element={<ProvisoesPage />} />
        <Route path="/receivables" element={<ReceivablesPage />} />
        {serverMode === 'server' && (
          <Route path="/servidor" element={<ServerDashboardPage />} />
        )}
        <Route path="/tunel" element={<TunnelPage />} />
      </Route>

      {/* Admin Panel Routes */}
      <Route element={<AdminLayout />}>
        <Route path="/admin" element={<AdminDashboardPage />} />
        <Route path="/admin/system-health" element={<div className="p-6"><h1 className="text-2xl font-bold">Saúde do Sistema</h1><p className="text-muted-foreground">Verificação de componentes em desenvolvimento...</p></div>} />
        <Route path="/admin/users" element={<div className="p-6"><h1 className="text-2xl font-bold">Gerenciamento de Usuários</h1><p className="text-muted-foreground">CRUD de usuários em desenvolvimento...</p></div>} />
        <Route path="/admin/permissions" element={<div className="p-6"><h1 className="text-2xl font-bold">Permissões e Roles</h1></div>} />
        <Route path="/admin/logs" element={<div className="p-6"><h1 className="text-2xl font-bold">Logs do Sistema</h1></div>} />
        <Route path="/admin/backups" element={<div className="p-6"><h1 className="text-2xl font-bold">Backups</h1></div>} />
        <Route path="/admin/containers" element={<div className="p-6"><h1 className="text-2xl font-bold">Infraestrutura</h1></div>} />
        <Route path="/admin/maintenance" element={<div className="p-6"><h1 className="text-2xl font-bold">Manutenção</h1></div>} />
        <Route path="/admin/integrity" element={<div className="p-6"><h1 className="text-2xl font-bold">Integridade</h1></div>} />
        <Route path="/admin/export" element={<div className="p-6"><h1 className="text-2xl font-bold">Exportação</h1></div>} />
        <Route path="/admin/server-config" element={<div className="p-6"><h1 className="text-2xl font-bold">Configuração de Servidor</h1></div>} />
        <Route path="/admin/updates" element={<UpdatePage />} />
      </Route>
    </Routes>
  )
}