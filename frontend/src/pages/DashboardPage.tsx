import { useAuth } from '../hooks/useAuth'
import { useDashboardOperational } from '../hooks/useDashboard'
import { Card, CardContent, CardHeader, CardTitle } from '../components/ui/Card'
import { Badge } from '../components/ui/Badge'
import {
  PieChart, Pie, Cell, BarChart, Bar, XAxis, YAxis, CartesianGrid,
  Tooltip, Legend, ResponsiveContainer
} from 'recharts'
import {
  Activity, AlertTriangle, Banknote, CalendarClock, CheckCircle2,
  CreditCard, Database, HardDrive, Heart, LayoutDashboard,
  ShieldCheck, TrendingUp, Users, Warehouse, XCircle
} from 'lucide-react'

const STATUS_COLORS: Record<string, string> = {
  pending: '#eab308',
  paid: '#22c55e',
  overdue: '#ef4444',
  cancelled: '#a3a3a3',
  draft: '#94a3b8',
  approved: '#3b82f6',
}

const STATUS_LABELS: Record<string, string> = {
  pending: 'Pendente',
  paid: 'Pago',
  overdue: 'Atrasado',
  cancelled: 'Cancelado',
  draft: 'Rascunho',
  approved: 'Aprovado',
}

function formatCurrency(value: number): string {
  return `R$ ${value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

function timeAgo(dateStr: string): string {
  const now = new Date()
  const date = new Date(dateStr)
  const diffMs = now.getTime() - date.getTime()
  const diffMin = Math.floor(diffMs / 60000)
  if (diffMin < 1) return 'agora'
  if (diffMin < 60) return `${diffMin}min atrás`
  const diffH = Math.floor(diffMin / 60)
  if (diffH < 24) return `${diffH}h atrás`
  const diffD = Math.floor(diffH / 24)
  return `${diffD}d atrás`
}

function ActionIcon({ action }: { action: string }) {
  if (action.includes('create') || action.includes('store')) return <CheckCircle2 className="h-4 w-4 text-green-500" />
  if (action.includes('delete') || action.includes('destroy')) return <XCircle className="h-4 w-4 text-red-500" />
  if (action.includes('update') || action.includes('edit')) return <Activity className="h-4 w-4 text-blue-500" />
  if (action.includes('login')) return <ShieldCheck className="h-4 w-4 text-purple-500" />
  if (action.includes('backup')) return <Database className="h-4 w-4 text-cyan-500" />
  return <Activity className="h-4 w-4 text-muted-foreground" />
}

function ModelLabel(modelType: string | null): string {
  if (!modelType) return ''
  const parts = modelType.split('\\')
  return parts[parts.length - 1]
}

export function DashboardPage() {
  const { user } = useAuth()
  const { data: operational, isLoading } = useDashboardOperational()

  const summary = operational?.summary
  const chartData = operational?.monthly_payables ?? []
  const activity = operational?.recent_activity ?? []
  const backup = operational?.backup
  const license = operational?.license
  const health = operational?.system_health ?? {}
  const payablesByStatus = operational?.payables_by_status ?? {}

  const pieData = Object.entries(payablesByStatus).map(([status, count]) => ({
    name: STATUS_LABELS[status] || status,
    value: count,
    status,
  }))

  const healthEntries = Object.entries(health)
  const allHealthy = healthEntries.every(([, h]) => h.status === 'ok')

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold tracking-tight flex items-center gap-2">
            <LayoutDashboard className="h-6 w-6" />
            Dashboard Operacional
          </h2>
          <p className="text-muted-foreground">
            Bem-vindo, {user.data?.name || 'Usuário'}
          </p>
        </div>
        <Badge variant={allHealthy ? 'success' : 'warning'}>
          <Heart className="mr-1 h-3 w-3" />
          {allHealthy ? 'Sistema OK' : 'Atenção'}
        </Badge>
      </div>

      {/* === KPI CARDS === */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
        <Card>
          <CardHeader className="pb-2">
            <p className="text-sm font-medium text-muted-foreground flex items-center gap-1">
              <Banknote className="h-4 w-4" /> Saldo Total
            </p>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {isLoading ? '...' : formatCurrency(summary?.total_balance ?? 0)}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <p className="text-sm font-medium text-muted-foreground flex items-center gap-1">
              <CreditCard className="h-4 w-4" /> Contas a Pagar
            </p>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{isLoading ? '...' : (summary?.pending_payables ?? 0)}</div>
            {(summary?.overdue_payables ?? 0) > 0 && (
              <Badge variant="destructive" className="mt-1 text-xs">
                {summary?.overdue_payables} atrasada(s)
              </Badge>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <p className="text-sm font-medium text-muted-foreground flex items-center gap-1">
              <CalendarClock className="h-4 w-4" /> Vencendo Hoje
            </p>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{isLoading ? '...' : (summary?.due_today ?? 0)}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <p className="text-sm font-medium text-muted-foreground flex items-center gap-1">
              <TrendingUp className="h-4 w-4" /> Pago no Mês
            </p>
          </CardHeader>
          <CardContent>
            <div className="text-xl font-bold">{isLoading ? '...' : formatCurrency(summary?.total_paid_this_month ?? 0)}</div>
            <p className="text-xs text-muted-foreground mt-1">
              {summary?.paid_this_month ?? 0} pagamento(s)
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <p className="text-sm font-medium text-muted-foreground flex items-center gap-1">
              <AlertTriangle className="h-4 w-4" /> Pré-Lançamentos
            </p>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{isLoading ? '...' : (summary?.pending_pre_launches ?? 0)}</div>
            <p className="text-xs text-muted-foreground mt-1">pendentes</p>
          </CardContent>
        </Card>
      </div>

      {/* === SECONDARY INDICATORS === */}
      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader className="pb-2">
            <p className="text-sm font-medium text-muted-foreground flex items-center gap-1">
              <Users className="h-4 w-4" /> Fornecedores Ativos
            </p>
          </CardHeader>
          <CardContent>
            <div className="text-xl font-bold">{isLoading ? '...' : (summary?.total_suppliers ?? 0)}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <p className="text-sm font-medium text-muted-foreground">Convênios Ativos</p>
          </CardHeader>
          <CardContent>
            <div className="text-xl font-bold">{isLoading ? '...' : (summary?.active_health_plans ?? 0)}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <p className="text-sm font-medium text-muted-foreground flex items-center gap-1">
              <Warehouse className="h-4 w-4" /> Contas Bancárias
            </p>
          </CardHeader>
          <CardContent>
            <div className="text-xl font-bold">{isLoading ? '...' : (summary?.total_bank_accounts ?? 0)}</div>
          </CardContent>
        </Card>
      </div>

      {/* === CHARTS === */}
      <div className="grid gap-4 lg:grid-cols-3">
        {/* Monthly Payables Trend */}
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle className="text-base">Fluxo de Pagamentos — Últimos 6 meses</CardTitle>
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <div className="flex items-center justify-center h-64 text-muted-foreground">Carregando...</div>
            ) : (
              <ResponsiveContainer width="100%" height={300}>
                <BarChart data={chartData} margin={{ top: 5, right: 20, left: 0, bottom: 5 }}>
                  <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                  <XAxis dataKey="month" className="text-xs" />
                  <YAxis className="text-xs" tickFormatter={(v) => `${(v / 1000).toFixed(0)}k`} />
                  <Tooltip
                    formatter={(value: number, name: string) => [
                      formatCurrency(value),
                      name === 'total' ? 'Vencido' : name === 'paid' ? 'Pago' : 'Atrasado'
                    ]}
                  />
                  <Legend />
                  <Bar dataKey="total" name="Vencido" fill="#3b82f6" radius={[4, 4, 0, 0]} />
                  <Bar dataKey="paid" name="Pago" fill="#22c55e" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            )}
          </CardContent>
        </Card>

        {/* Payables by Status Pie */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Status das Contas</CardTitle>
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <div className="flex items-center justify-center h-64 text-muted-foreground">Carregando...</div>
            ) : pieData.length === 0 ? (
              <div className="flex items-center justify-center h-64 text-muted-foreground">Sem dados</div>
            ) : (
              <ResponsiveContainer width="100%" height={260}>
                <PieChart>
                  <Pie
                    data={pieData}
                    cx="50%"
                    cy="50%"
                    innerRadius={50}
                    outerRadius={90}
                    paddingAngle={4}
                    dataKey="value"
                    label={({ name, value }) => `${name}: ${value}`}
                  >
                    {pieData.map((entry, index) => (
                      <Cell
                        key={`cell-${index}`}
                        fill={STATUS_COLORS[entry.status] || '#94a3b8'}
                      />
                    ))}
                  </Pie>
                  <Tooltip />
                </PieChart>
              </ResponsiveContainer>
            )}
          </CardContent>
        </Card>
      </div>

      {/* === BOTTOM ROW: Activity + Backup + License + Health === */}
      <div className="grid gap-4 lg:grid-cols-3">
        {/* Activity Feed */}
        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <Activity className="h-4 w-4" /> Atividade Recente
            </CardTitle>
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <div className="text-muted-foreground text-sm">Carregando...</div>
            ) : activity.length === 0 ? (
              <div className="text-muted-foreground text-sm">Nenhuma atividade registrada</div>
            ) : (
              <div className="space-y-3 max-h-72 overflow-y-auto">
                {activity.map((item) => (
                  <div key={item.id} className="flex items-start gap-2 text-sm">
                    <ActionIcon action={item.action} />
                    <div className="flex-1 min-w-0">
                      <p className="truncate">
                        <span className="font-medium">{item.user_name}</span>
                        {' '}
                        <span className="text-muted-foreground">{item.action.replace(/_/g, ' ')}</span>
                        {item.model_type && (
                          <span className="text-muted-foreground">
                            {' '}— {ModelLabel(item.model_type)}{item.model_id ? ` #${item.model_id}` : ''}
                          </span>
                        )}
                      </p>
                      <p className="text-xs text-muted-foreground">{timeAgo(item.created_at)}</p>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        {/* Backup + License Status */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <Database className="h-4 w-4" /> Backup & Licença
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <p className="text-sm font-medium text-muted-foreground">Backup</p>
              {backup ? (
                <div className="mt-1 space-y-1">
                  <div className="flex items-center gap-2 text-sm">
                    <CheckCircle2 className="h-4 w-4 text-green-500" />
                    <span>{backup.total_backups} backup(s) concluído(s)</span>
                  </div>
                  {backup.last_backup_at && (
                    <p className="text-xs text-muted-foreground">
                      Último: {new Date(backup.last_backup_at).toLocaleString('pt-BR')}
                    </p>
                  )}
                  {backup.last_backup_size && (
                    <p className="text-xs text-muted-foreground">
                      Tamanho: {(backup.last_backup_size / 1024 / 1024).toFixed(1)} MB
                    </p>
                  )}
                </div>
              ) : (
                <p className="text-xs text-muted-foreground mt-1">Nenhum backup registrado</p>
              )}
            </div>

            <hr className="border-border" />

            <div>
              <p className="text-sm font-medium text-muted-foreground">Licença</p>
              {license ? (
                <div className="mt-1 space-y-1">
                  <div className="flex items-center gap-2 text-sm">
                    <ShieldCheck className="h-4 w-4 text-green-500" />
                    <span className="font-medium">{license.type}</span>
                    <Badge variant="success" className="text-[10px]">{license.status}</Badge>
                  </div>
                  <p className="text-xs text-muted-foreground">Chave: {license.key}</p>
                  {license.customer_name && (
                    <p className="text-xs text-muted-foreground">Cliente: {license.customer_name}</p>
                  )}
                  {license.expires_at && (
                    <p className="text-xs text-muted-foreground">
                      Expira: {new Date(license.expires_at).toLocaleDateString('pt-BR')}
                    </p>
                  )}
                </div>
              ) : (
                <div className="mt-1 flex items-center gap-2 text-sm">
                  <AlertTriangle className="h-4 w-4 text-yellow-500" />
                  <span className="text-muted-foreground">Sem licença ativa</span>
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        {/* System Health */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <HardDrive className="h-4 w-4" /> Saúde do Sistema
            </CardTitle>
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <div className="text-muted-foreground text-sm">Carregando...</div>
            ) : (
              <div className="space-y-3">
                {healthEntries.map(([key, check]) => (
                  <div key={key} className="flex items-center justify-between text-sm">
                    <span className="flex items-center gap-2 capitalize">
                      {check.status === 'ok' ? (
                        <CheckCircle2 className="h-4 w-4 text-green-500" />
                      ) : check.status === 'warning' ? (
                        <AlertTriangle className="h-4 w-4 text-yellow-500" />
                      ) : (
                        <XCircle className="h-4 w-4 text-red-500" />
                      )}
                      {key === 'mysql' ? 'MySQL' : key === 'audit' ? 'Auditoria' : key === 'storage' ? 'Armazenamento' : key === 'queue' ? 'Fila' : key}
                    </span>
                    <span className="text-xs text-muted-foreground">
                      {check.latency_ms != null && `${check.latency_ms}ms`}
                      {check.used_formatted && check.used_formatted}
                      {check.info && check.info}
                      {check.message && check.message}
                    </span>
                  </div>
                ))}
                {healthEntries.length === 0 && (
                  <p className="text-muted-foreground text-sm">Sem dados de saúde</p>
                )}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
