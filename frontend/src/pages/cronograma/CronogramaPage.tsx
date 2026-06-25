import { useCronograma } from '../../hooks/useCronograma'
import { Card, CardHeader, CardContent } from '../../components/ui/Card'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../../components/ui/Table'
import { Badge } from '../../components/ui/Badge'

export function CronogramaPage() {
  const { data, isLoading } = useCronograma()

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Cronograma Financeiro</h2>
          <p className="text-muted-foreground">Projeção de saldos a 30, 60, 90, 180 e 365 dias</p>
        </div>
      </div>

      {isLoading ? (
        <div className="text-center py-8 text-muted-foreground">Carregando...</div>
      ) : data ? (
        <>
          <div className="grid gap-4 md:grid-cols-3">
            <Card>
              <CardHeader className="pb-2">
                <p className="text-sm font-medium text-muted-foreground">Saldo Atual</p>
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">
                  R$ {data.summary.current_balance.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="pb-2">
                <p className="text-sm font-medium text-muted-foreground">Pagamentos Pendentes</p>
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-destructive">
                  R$ {data.summary.total_pending_outflows.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="pb-2">
                <p className="text-sm font-medium text-muted-foreground">Entradas Projetadas</p>
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-green-600">
                  R$ {data.summary.total_projected_inflows.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                </div>
              </CardContent>
            </Card>
          </div>

          <Card>
            <CardHeader>
              <h3 className="text-lg font-semibold">Projeção por Prazo</h3>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Prazo</TableHead>
                    <TableHead>Data</TableHead>
                    <TableHead>Saídas</TableHead>
                    <TableHead>Entradas</TableHead>
                    <TableHead>Saldo Projetado</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data.projections.map((proj) => (
                    <TableRow key={proj.horizon_days}>
                      <TableCell className="font-medium">{proj.horizon_days} dias</TableCell>
                      <TableCell>{new Date(proj.target_date).toLocaleDateString('pt-BR')}</TableCell>
                      <TableCell className="text-destructive">
                        R$ {proj.outflows.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                      </TableCell>
                      <TableCell className="text-green-600">
                        R$ {proj.inflows.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                      </TableCell>
                      <TableCell className={proj.projected_balance >= 0 ? 'font-bold' : 'font-bold text-destructive'}>
                        R$ {proj.projected_balance.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                      </TableCell>
                      <TableCell>
                        <Badge variant={proj.projected_balance >= 0 ? 'success' : 'destructive'}>
                          {proj.projected_balance >= 0 ? 'Positivo' : 'Negativo'}
                        </Badge>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>

          {data.daily_breakdown.length > 0 && (
            <Card>
              <CardHeader>
                <h3 className="text-lg font-semibold">Fluxo Diário (90 dias)</h3>
              </CardHeader>
              <CardContent>
                <div className="max-h-96 overflow-y-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Data</TableHead>
                        <TableHead>Saídas</TableHead>
                        <TableHead>Saldo</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {data.daily_breakdown.map((day) => (
                        <TableRow key={day.date}>
                          <TableCell>{new Date(day.date).toLocaleDateString('pt-BR')}</TableCell>
                          <TableCell className={day.outflows > 0 ? 'text-destructive' : ''}>
                            {day.outflows > 0 ? `R$ ${day.outflows.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}` : '-'}
                          </TableCell>
                          <TableCell className={day.running_balance >= 0 ? 'font-medium' : 'font-medium text-destructive'}>
                            R$ {day.running_balance.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              </CardContent>
            </Card>
          )}
        </>
      ) : null}
    </div>
  )
}

export default CronogramaPage
