import { useState } from 'react'
import { useSupplierReport, useCashFlowReport, usePrestacaoContas } from '../../hooks/useReports'
import { useHealthPlans } from '../../hooks/useHealthPlans'
import { Card, CardHeader, CardContent } from '../../components/ui/Card'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../../components/ui/Table'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Badge } from '../../components/ui/Badge'

type ReportTab = 'suppliers' | 'cashflow' | 'prestacao'

export function ReportsPage() {
  const [activeTab, setActiveTab] = useState<ReportTab>('suppliers')

  const tabs: { key: ReportTab; label: string }[] = [
    { key: 'suppliers', label: 'Por Fornecedor' },
    { key: 'cashflow', label: 'Fluxo de Caixa' },
    { key: 'prestacao', label: 'Prestação de Contas' },
  ]

  return (
    <div className="space-y-4">
      <div>
        <h2 className="text-2xl font-bold tracking-tight">Relatórios</h2>
        <p className="text-muted-foreground">Análises financeiras e prestações de contas</p>
      </div>

      <div className="flex gap-2">
        {tabs.map((tab) => (
          <Button
            key={tab.key}
            variant={activeTab === tab.key ? 'default' : 'outline'}
            onClick={() => setActiveTab(tab.key)}
          >
            {tab.label}
          </Button>
        ))}
      </div>

      {activeTab === 'suppliers' && <SupplierReport />}
      {activeTab === 'cashflow' && <CashFlowReport />}
      {activeTab === 'prestacao' && <PrestacaoContasReport />}
    </div>
  )
}

function SupplierReport() {
  const [dateFrom, setDateFrom] = useState('')
  const [dateTo, setDateTo] = useState('')
  const { data, isLoading, refetch } = useSupplierReport({ date_from: dateFrom || undefined, date_to: dateTo || undefined })

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <h3 className="text-lg font-semibold">Relatório por Fornecedor</h3>
        <div className="flex gap-2 items-center">
          <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="max-w-[160px]" />
          <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="max-w-[160px]" />
          <Button variant="outline" size="sm" onClick={() => refetch()}>Filtrar</Button>
        </div>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="text-center py-8 text-muted-foreground">Carregando...</div>
        ) : data ? (
          <>
            <div className="grid gap-4 md:grid-cols-4 mb-6">
              <div className="rounded-lg border p-4">
                <p className="text-sm text-muted-foreground">Total Fornecedores</p>
                <p className="text-xl font-bold">{data.summary.total_suppliers}</p>
              </div>
              <div className="rounded-lg border p-4">
                <p className="text-sm text-muted-foreground">Total Geral</p>
                <p className="text-xl font-bold">R$ {data.summary.total_amount.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
              </div>
              <div className="rounded-lg border p-4">
                <p className="text-sm text-muted-foreground">Pago</p>
                <p className="text-xl font-bold text-green-600">R$ {data.summary.total_paid.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
              </div>
              <div className="rounded-lg border p-4">
                <p className="text-sm text-muted-foreground">Pendente</p>
                <p className="text-xl font-bold text-destructive">R$ {data.summary.total_pending.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
              </div>
            </div>

            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Fornecedor</TableHead>
                  <TableHead>Qtd</TableHead>
                  <TableHead>Total</TableHead>
                  <TableHead>Pago</TableHead>
                  <TableHead>Pendente</TableHead>
                  <TableHead>Atrasado</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.suppliers.map((s) => (
                  <TableRow key={s.supplier_id}>
                    <TableCell className="font-medium">{s.supplier_name}</TableCell>
                    <TableCell>{s.count}</TableCell>
                    <TableCell>R$ {s.total_amount.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</TableCell>
                    <TableCell className="text-green-600">R$ {s.total_paid.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</TableCell>
                    <TableCell>R$ {s.total_pending.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</TableCell>
                    <TableCell>
                      {s.total_overdue > 0 ? (
                        <Badge variant="destructive">R$ {s.total_overdue.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</Badge>
                      ) : '-'}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </>
        ) : null}
      </CardContent>
    </Card>
  )
}

function CashFlowReport() {
  const today = new Date()
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0]
  const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0]

  const [dateFrom, setDateFrom] = useState(firstDay)
  const [dateTo, setDateTo] = useState(lastDay)
  const { data, isLoading, refetch } = useCashFlowReport({ date_from: dateFrom, date_to: dateTo })

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <h3 className="text-lg font-semibold">Fluxo de Caixa</h3>
        <div className="flex gap-2 items-center">
          <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="max-w-[160px]" />
          <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="max-w-[160px]" />
          <Button variant="outline" size="sm" onClick={() => refetch()}>Filtrar</Button>
        </div>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="text-center py-8 text-muted-foreground">Carregando...</div>
        ) : data ? (
          <>
            <div className="grid gap-4 md:grid-cols-3 mb-6">
              <div className="rounded-lg border p-4">
                <p className="text-sm text-muted-foreground">Pago no Período</p>
                <p className="text-xl font-bold text-green-600">R$ {data.outflows.paid.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
              </div>
              <div className="rounded-lg border p-4">
                <p className="text-sm text-muted-foreground">A Pagar</p>
                <p className="text-xl font-bold text-destructive">R$ {data.outflows.pending.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
              </div>
              <div className="rounded-lg border p-4">
                <p className="text-sm text-muted-foreground">Total do Período</p>
                <p className="text-xl font-bold">R$ {data.outflows.total.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
              </div>
            </div>

            {Object.keys(data.by_method).length > 0 && (
              <>
                <h4 className="font-semibold mb-3">Por Forma de Pagamento</h4>
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Forma</TableHead>
                      <TableHead>Qtd</TableHead>
                      <TableHead>Total</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {Object.entries(data.by_method).map(([method, info]) => (
                      <TableRow key={method}>
                        <TableCell className="font-medium">{method || 'Não informado'}</TableCell>
                        <TableCell>{info.count}</TableCell>
                        <TableCell>R$ {info.total.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </>
            )}
          </>
        ) : null}
      </CardContent>
    </Card>
  )
}

function PrestacaoContasReport() {
  const [healthPlanId, setHealthPlanId] = useState('')
  const [dateFrom, setDateFrom] = useState('')
  const [dateTo, setDateTo] = useState('')
  const { data: healthPlans } = useHealthPlans({ per_page: 100 })
  const { data, isLoading, refetch } = usePrestacaoContas({
    health_plan_id: healthPlanId || undefined,
    date_from: dateFrom || undefined,
    date_to: dateTo || undefined,
  })

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <h3 className="text-lg font-semibold">Prestação de Contas</h3>
        <div className="flex gap-2 items-center">
          <select
            value={healthPlanId}
            onChange={(e) => setHealthPlanId(e.target.value)}
            className="border rounded-md px-3 py-2 text-sm"
          >
            <option value="">Todos os convênios</option>
            {healthPlans?.data?.map((h: any) => (
              <option key={h.id} value={h.id}>{h.name}</option>
            ))}
          </select>
          <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="max-w-[160px]" />
          <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="max-w-[160px]" />
          <Button variant="outline" size="sm" onClick={() => refetch()}>Filtrar</Button>
        </div>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="text-center py-8 text-muted-foreground">Carregando...</div>
        ) : data ? (
          <>
            {data.health_plan && (
              <div className="mb-4 p-3 bg-muted rounded-lg">
                <p className="font-semibold">{data.health_plan.name} ({data.health_plan.code})</p>
                <p className="text-sm text-muted-foreground">
                  Período: {new Date(data.period.from).toLocaleDateString('pt-BR')} a {new Date(data.period.to).toLocaleDateString('pt-BR')}
                </p>
              </div>
            )}

            <div className="grid gap-4 md:grid-cols-4 mb-6">
              <div className="rounded-lg border p-4">
                <p className="text-sm text-muted-foreground">Total Lançamentos</p>
                <p className="text-xl font-bold">{data.summary.total_entries}</p>
              </div>
              <div className="rounded-lg border p-4">
                <p className="text-sm text-muted-foreground">Total Geral</p>
                <p className="text-xl font-bold">R$ {data.summary.total_amount.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
              </div>
              <div className="rounded-lg border p-4">
                <p className="text-sm text-muted-foreground">Pago</p>
                <p className="text-xl font-bold text-green-600">R$ {data.summary.total_paid.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
              </div>
              <div className="rounded-lg border p-4">
                <p className="text-sm text-muted-foreground">Pendente</p>
                <p className="text-xl font-bold text-destructive">R$ {data.summary.total_pending.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
              </div>
            </div>

            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Descrição</TableHead>
                  <TableHead>Fornecedor</TableHead>
                  <TableHead>Categoria</TableHead>
                  <TableHead>Vencimento</TableHead>
                  <TableHead>Valor</TableHead>
                  <TableHead>Pago</TableHead>
                  <TableHead>Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.entries.map((entry) => (
                  <TableRow key={entry.id}>
                    <TableCell className="font-medium">{entry.description}</TableCell>
                    <TableCell>{entry.supplier || '-'}</TableCell>
                    <TableCell>{entry.category || '-'}</TableCell>
                    <TableCell>{new Date(entry.due_date).toLocaleDateString('pt-BR')}</TableCell>
                    <TableCell>R$ {entry.amount.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</TableCell>
                    <TableCell>{entry.paid_amount ? `R$ ${entry.paid_amount.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}` : '-'}</TableCell>
                    <TableCell>
                      <Badge variant={entry.status === 'paid' ? 'success' : entry.status === 'overdue' ? 'destructive' : 'secondary'}>
                        {entry.status === 'paid' ? 'Pago' : entry.status === 'overdue' ? 'Atrasado' : 'Pendente'}
                      </Badge>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </>
        ) : null}
      </CardContent>
    </Card>
  )
}

export default ReportsPage
