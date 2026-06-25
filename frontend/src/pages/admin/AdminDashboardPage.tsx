import { useQuery } from '@tanstack/react-query'
import { adminApi } from '@/services/adminApi'
import { Card, CardContent } from '@/components/ui/Card'
import { CheckCircle, XCircle, Database, Users, FileText, Banknote, Activity, Shield } from 'lucide-react'

function StatCard({ label, value, icon: Icon, color }: { label: string; value: number | string; icon: any; color: string }) {
  return (
    <Card>
      <CardContent className="p-4">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="text-2xl font-bold mt-1">{value}</p>
          </div>
          <div className={`p-2 rounded-lg ${color}`}>
            <Icon className="w-5 h-5" />
          </div>
        </div>
      </CardContent>
    </Card>
  )
}

export function AdminDashboardPage() {
  const { data: dashboard, isLoading } = useQuery({
    queryKey: ['admin-dashboard'],
    queryFn: async () => {
      const res = await adminApi.get('/dashboard')
      return res.data
    },
    refetchInterval: 30000,
  })

  if (isLoading) {
    return (
      <div className="p-6 space-y-4">
        <div className="h-8 w-48 bg-muted rounded animate-pulse" />
        <div className="grid grid-cols-4 gap-4">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="h-24 bg-muted rounded animate-pulse" />
          ))}
        </div>
      </div>
    )
  }

  const db = dashboard?.database || {}
  const usr = dashboard?.usuarios_sistema || {}
  const bkp = dashboard?.backup || {}
  const sec = dashboard?.security || {}

  return (
    <div className="p-6 space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Dashboard Administrativo</h1>
        <p className="text-sm text-muted-foreground mt-1">
          VisÃ£o geral do sistema SCHF
        </p>
      </div>

      {/* Database Stats */}
      <div>
        <h2 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">
          Dados HistÃ³ricos
        </h2>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <StatCard label="Fornecedores" value={db.fornecedores || 0} icon={Users} color="bg-blue-100 text-blue-600" />
          <StatCard label="Notas Fiscais" value={db.notas || 0} icon={FileText} color="bg-purple-100 text-purple-600" />
          <StatCard label="OperaÃ§Ãµes BancÃ¡rias" value={db.operacoes_banco || 0} icon={Banknote} color="bg-green-100 text-green-600" />
          <StatCard label="Baixas Perdidas" value={db.baixas_perdidas || 0} icon={XCircle} color="bg-red-100 text-red-600" />
          <StatCard label="Caixas" value={db.caixas || 0} icon={Activity} color="bg-orange-100 text-orange-600" />
          <StatCard label="Contas" value={db.contas || 0} icon={Database} color="bg-cyan-100 text-cyan-600" />
          <StatCard label="ConvÃªnios" value={db.convenios || 0} icon={Shield} color="bg-teal-100 text-teal-600" />
          <StatCard label="UsuÃ¡rios" value={db.usuarios || 0} icon={Users} color="bg-indigo-100 text-indigo-600" />
        </div>
      </div>

      {/* System Users */}
      <div>
        <h2 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">
          UsuÃ¡rios do Sistema
        </h2>
        <div className="grid grid-cols-4 gap-4">
          <StatCard label="Total" value={usr.total || 0} icon={Users} color="bg-slate-100 text-slate-600" />
          <StatCard label="Ativos" value={usr.ativos || 0} icon={CheckCircle} color="bg-green-100 text-green-600" />
          <StatCard label="Inativos" value={usr.inativos || 0} icon={XCircle} color="bg-red-100 text-red-600" />
          <StatCard label="Masters" value={usr.masters || 0} icon={Shield} color="bg-amber-100 text-amber-600" />
        </div>
      </div>

      {/* Security */}
      <div>
        <h2 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">
          SeguranÃ§a
        </h2>
        <div className="grid grid-cols-3 gap-4">
          <Card>
            <CardContent className="p-4">
              <p className="text-xs text-muted-foreground">Falhas de login (24h)</p>
              <p className="text-2xl font-bold text-red-600 mt-1">{sec.falhas_login_24h || 0}</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <p className="text-xs text-muted-foreground">UsuÃ¡rios ativos (1h)</p>
              <p className="text-2xl font-bold text-green-600 mt-1">{sec.usuarios_ativos_1h || 0}</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <p className="text-xs text-muted-foreground">Ãšltimo login admin</p>
              <p className="text-sm font-medium mt-1">
                {sec.ultimo_login_admin
                  ? new Date(sec.ultimo_login_admin).toLocaleString('pt-BR')
                  : 'Nunca'}
              </p>
            </CardContent>
          </Card>
        </div>
      </div>

      {/* Backups */}
      <div>
        <h2 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">
          Backup
        </h2>
        <div className="grid grid-cols-3 gap-4">
          <Card>
            <CardContent className="p-4">
              <p className="text-xs text-muted-foreground">Total de backups</p>
              <p className="text-2xl font-bold mt-1">{bkp.total_backups || 0}</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <p className="text-xs text-muted-foreground">Ãšltimo backup</p>
              <p className="text-sm font-medium mt-1">
                {bkp.ultimo_backup
                  ? new Date(bkp.ultimo_backup).toLocaleString('pt-BR')
                  : 'Nenhum'}
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <p className="text-xs text-muted-foreground">Tamanho total</p>
              <p className="text-2xl font-bold mt-1">{bkp.tamanho_total_formatado || '0 B'}</p>
            </CardContent>
          </Card>
        </div>
      </div>

      {/* Quick Info */}
      <div className="grid grid-cols-2 gap-4">
        <Card>
          <CardContent className="p-4">
            <p className="text-xs text-muted-foreground">VersÃ£o do Sistema</p>
            <p className="text-sm font-bold mt-1">SCHF v0.3.0</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-4">
            <p className="text-xs text-muted-foreground">Ambiente</p>
            <p className="text-sm font-bold mt-1">ProduÃ§Ã£o Docker</p>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
