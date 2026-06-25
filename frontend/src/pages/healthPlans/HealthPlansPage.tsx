
import { useHealthPlans } from '../../hooks/useHealthPlans'
import { Button } from '../../components/ui/Button'
import { Card, CardContent } from '../../components/ui/Card'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../../components/ui/Table'
import { Badge } from '../../components/ui/Badge'
import { formatCurrency } from '../../utils/format'

export function HealthPlansPage() {
  const { data, isLoading } = useHealthPlans()

  const typeLabels: Record<string, string> = {
    sus: 'SUS',
    convenio: 'Convênio',
    particular: 'Particular',
    emenda: 'Emenda',
    municipal: 'Municipal',
    outro: 'Outro',
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Convênios / Planos de Recurso</h2>
          <p className="text-muted-foreground">Convênios, SUS, emendas e recursos vinculados</p>
        </div>
        <Button>Novo Convênio</Button>
      </div>

      <Card>
        <CardContent className="pt-6">
          {isLoading ? (
            <div className="text-center py-8 text-muted-foreground">Carregando...</div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nome</TableHead>
                  <TableHead>Código</TableHead>
                  <TableHead>Tipo</TableHead>
                  <TableHead>Saldo</TableHead>
                  <TableHead>Comprometido</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Ações</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data?.data?.map((plan: any) => (
                  <TableRow key={plan.id}>
                    <TableCell className="font-medium">{plan.name}</TableCell>
                    <TableCell>{plan.code}</TableCell>
                    <TableCell>
                      <Badge variant="outline">{typeLabels[plan.type] || plan.type}</Badge>
                    </TableCell>
                    <TableCell>{formatCurrency(plan.balance)}</TableCell>
                    <TableCell>{formatCurrency(plan.committed_balance)}</TableCell>
                    <TableCell>
                      <Badge variant={plan.is_active ? 'success' : 'secondary'}>
                        {plan.is_active ? 'Ativo' : 'Inativo'}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right space-x-2">
                      <Button variant="outline" size="sm">Editar</Button>
                      <Button variant="outline" size="sm">Ver Saldo</Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
