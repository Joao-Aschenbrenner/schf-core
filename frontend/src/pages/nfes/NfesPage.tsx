import { useState } from 'react'
import { useNfes, useDeleteNfe, useConfirmNfe } from '../../hooks/useNfes'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Card, CardHeader, CardContent } from '../../components/ui/Card'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../../components/ui/Table'
import { Badge } from '../../components/ui/Badge'
import NfeForm from './NfeForm'

const statusMap: Record<string, { label: string; variant: string }> = {
  draft: { label: 'Rascunho', variant: 'secondary' },
  confirmed: { label: 'Confirmada', variant: 'success' },
  cancelled: { label: 'Cancelada', variant: 'destructive' },
}

export function NfesPage() {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [page, setPage] = useState(1)
  const [showForm, setShowForm] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)

  const { data, isLoading } = useNfes({ search, status: statusFilter || undefined, page, per_page: 15 })
  const deleteMutation = useDeleteNfe()
  const confirmMutation = useConfirmNfe()

  const handleEdit = (id: number) => {
    setEditingId(id)
    setShowForm(true)
  }

  const handleConfirm = async (id: number) => {
    if (confirm('Confirma esta NF-e? Após confirmação, não será possível editar.')) {
      await confirmMutation.mutateAsync(id)
    }
  }

  const handleDelete = async (id: number) => {
    if (confirm('Confirma exclusão desta NF-e?')) {
      await deleteMutation.mutateAsync(id)
    }
  }

  const handleCloseForm = () => {
    setShowForm(false)
    setEditingId(null)
  }

  if (showForm) {
    return <NfeForm id={editingId} onClose={handleCloseForm} />
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Notas Fiscais (NF-e)</h2>
          <p className="text-muted-foreground">Gestão de notas fiscais de entrada</p>
        </div>
        <Button onClick={() => setShowForm(true)}>Nova NF-e</Button>
      </div>

      <Card>
        <CardHeader>
          <div className="flex gap-4">
            <Input
              placeholder="Buscar por número, chave, descrição..."
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
              <option value="confirmed">Confirmada</option>
              <option value="cancelled">Cancelada</option>
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
                  <TableHead>Número</TableHead>
                  <TableHead>Fornecedor</TableHead>
                  <TableHead>Emissão</TableHead>
                  <TableHead>Valor Total</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Ações</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data?.data?.map((nfe: any) => (
                  <TableRow key={nfe.id}>
                    <TableCell className="font-medium">{nfe.nfe_number}</TableCell>
                    <TableCell>{nfe.supplier?.name || '-'}</TableCell>
                    <TableCell>{nfe.emission_date ? new Date(nfe.emission_date).toLocaleDateString('pt-BR') : '-'}</TableCell>
                    <TableCell>R$ {Number(nfe.total_value).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</TableCell>
                    <TableCell>
                      <Badge variant={statusMap[nfe.status]?.variant as any || 'secondary'}>
                        {statusMap[nfe.status]?.label || nfe.status}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right space-x-2">
                      {nfe.status === 'draft' && (
                        <>
                          <Button variant="outline" size="sm" onClick={() => handleEdit(nfe.id)}>
                            Editar
                          </Button>
                          <Button variant="default" size="sm" onClick={() => handleConfirm(nfe.id)}>
                            Confirmar
                          </Button>
                        </>
                      )}
                      <Button variant="destructive" size="sm" onClick={() => handleDelete(nfe.id)}>
                        Excluir
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}

          {data?.meta && (
            <div className="flex items-center justify-between mt-4">
              <p className="text-sm text-muted-foreground">
                {data.meta.total} NF-e(s) encontrada(s)
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

export default NfesPage
