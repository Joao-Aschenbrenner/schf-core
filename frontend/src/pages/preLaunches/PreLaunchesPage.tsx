import { useState } from 'react'
import { usePreLaunches, useConfirmPreLaunch, useCancelPreLaunch } from '../../hooks/usePreLaunches'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Card, CardHeader, CardContent } from '../../components/ui/Card'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../../components/ui/Table'
import { Badge } from '../../components/ui/Badge'
import PreLaunchForm from './PreLaunchForm'

const typeMap: Record<string, string> = {
  payroll: 'Folha de Pagamento',
  medical_fees: 'Honorários Médicos',
  tax: 'Impostos',
  supplier: 'Fornecedores',
  recurring: 'Despesas Recorrentes',
}

const statusMap: Record<string, { label: string; variant: string }> = {
  draft: { label: 'Rascunho', variant: 'secondary' },
  confirmed: { label: 'Confirmado', variant: 'success' },
  converted: { label: 'Convertido', variant: 'default' },
  cancelled: { label: 'Cancelado', variant: 'destructive' },
}

export function PreLaunchesPage() {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [page, setPage] = useState(1)
  const [showForm, setShowForm] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)

  const { data, isLoading } = usePreLaunches({ search, status: statusFilter || undefined, page, per_page: 15 })
  const confirmMutation = useConfirmPreLaunch()
  const cancelMutation = useCancelPreLaunch()

  const handleEdit = (id: number) => {
    setEditingId(id)
    setShowForm(true)
  }

  const handleConfirm = async (id: number) => {
    if (confirm('Confirma este pré-lançamento? Será gerado um payable correspondente.')) {
      await confirmMutation.mutateAsync(id)
    }
  }

  const handleCancel = async (id: number) => {
    if (confirm('Confirma cancelamento deste pré-lançamento?')) {
      await cancelMutation.mutateAsync(id)
    }
  }

  const handleCloseForm = () => {
    setShowForm(false)
    setEditingId(null)
  }

  if (showForm) {
    return <PreLaunchForm id={editingId} onClose={handleCloseForm} />
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Pré-Lançamentos</h2>
          <p className="text-muted-foreground">Lançamentos pendentes de confirmação (folha, impostos, fornecedores)</p>
        </div>
        <Button onClick={() => setShowForm(true)}>Novo Pré-Lançamento</Button>
      </div>

      <Card>
        <CardHeader>
          <div className="flex gap-4">
            <Input
              placeholder="Buscar por descrição..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="max-w-sm"
            />
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="border rounded-md px-3 py-2 text-sm"
            >
              <option value="">Todos os status</option>
              <option value="draft">Rascunho</option>
              <option value="confirmed">Confirmado</option>
              <option value="converted">Convertido</option>
              <option value="cancelled">Cancelado</option>
            </select>
          </div>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8 text-muted-foreground">Carregando...</div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Descrição</TableHead>
                  <TableHead>Tipo</TableHead>
                  <TableHead>Vencimento</TableHead>
                  <TableHead>Valor</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Ações</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data?.data?.map((pl: any) => (
                  <TableRow key={pl.id}>
                    <TableCell className="font-medium">{pl.description}</TableCell>
                    <TableCell>{typeMap[pl.type] || pl.type}</TableCell>
                    <TableCell>
                      {pl.due_date ? new Date(pl.due_date).toLocaleDateString('pt-BR') : '-'}
                    </TableCell>
                    <TableCell>
                      R$ {Number(pl.amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                    </TableCell>
                    <TableCell>
                      <Badge variant={statusMap[pl.status]?.variant as any || 'secondary'}>
                        {statusMap[pl.status]?.label || pl.status}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right space-x-2">
                      {pl.status === 'draft' && (
                        <>
                          <Button variant="outline" size="sm" onClick={() => handleEdit(pl.id)}>
                            Editar
                          </Button>
                          <Button variant="default" size="sm" onClick={() => handleConfirm(pl.id)}>
                            Confirmar
                          </Button>
                        </>
                      )}
                      {pl.status !== 'cancelled' && pl.status !== 'converted' && (
                        <Button variant="destructive" size="sm" onClick={() => handleCancel(pl.id)}>
                          Cancelar
                        </Button>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}

          {data?.meta && (
            <div className="flex items-center justify-between mt-4">
              <p className="text-sm text-muted-foreground">
                {data.meta.total} pré-lançamento(s) encontrado(s)
              </p>
              <div className="space-x-2">
                <Button
                  variant="outline"
                  size="sm"
                  disabled={page <= 1}
                  onClick={() => setPage(page - 1)}
                >
                  Anterior
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  disabled={page >= data.meta.last_page}
                  onClick={() => setPage(page + 1)}
                >
                  Próximo
                </Button>
              </div>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}

export default PreLaunchesPage
