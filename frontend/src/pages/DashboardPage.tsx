import { useAuth } from '../hooks/useAuth'
import { useDashboardSummary } from '../hooks/useDashboard'
import { Card, CardContent, CardHeader } from '../components/ui/Card'
import { Badge } from '../components/ui/Badge'

export function DashboardPage() {
  const { user } = useAuth()
  const { data: summary, isLoading } = useDashboardSummary()

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold tracking-tight">Dashboard</h2>
        <p className="text-muted-foreground">
          Bem-vindo, {user.data?.name || 'Usuário'}
        </p>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="pb-2">
            <p className="text-sm font-medium text-muted-foreground">Saldo Total</p>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {isLoading ? '...' : `R$ ${(summary?.total_balance || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <p className="text-sm font-medium text-muted-foreground">Contas a Pagar</p>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{isLoading ? '...' : (summary?.pending_payables || 0)}</div>
            {(summary?.overdue_payables || 0) > 0 && (
              <Badge variant="destructive" className="mt-1 text-xs">
                {summary?.overdue_payables} atrasada(s)
              </Badge>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <p className="text-sm font-medium text-muted-foreground">Vencendo Hoje</p>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{isLoading ? '...' : (summary?.due_today || 0)}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <p className="text-sm font-medium text-muted-foreground">Pré-Lançamentos</p>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{isLoading ? '...' : (summary?.pending_pre_launches || 0)}</div>
            <p className="text-xs text-muted-foreground mt-1">pendentes de confirmação</p>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader className="pb-2">
            <p className="text-sm font-medium text-muted-foreground">Fornecedores Ativos</p>
          </CardHeader>
          <CardContent>
            <div className="text-xl font-bold">{isLoading ? '...' : (summary?.total_suppliers || 0)}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <p className="text-sm font-medium text-muted-foreground">Convênios Ativos</p>
          </CardHeader>
          <CardContent>
            <div className="text-xl font-bold">{isLoading ? '...' : (summary?.active_health_plans || 0)}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <p className="text-sm font-medium text-muted-foreground">Contas Bancárias</p>
          </CardHeader>
          <CardContent>
            <div className="text-xl font-bold">{isLoading ? '...' : (summary?.total_bank_accounts || 0)}</div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
