import { useState } from 'react'
import { useSuppliers, useDeleteSupplier } from '../../hooks/useSuppliers'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Card, CardHeader, CardContent } from '../../components/ui/Card'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../../components/ui/Table'
import { Badge } from '../../components/ui/Badge'
import SupplierForm from './SupplierForm'

export function SuppliersPage() {
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [showForm, setShowForm] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)

  const { data, isLoading } = useSuppliers({ search, page, per_page: 15 })
  const deleteMutation = useDeleteSupplier()

  const handleEdit = (id: number) => {
    setEditingId(id)
    setShowForm(true)
  }

  const handleDelete = async (id: number) => {
    if (confirm('Confirma inativação deste fornecedor?')) {
      await deleteMutation.mutateAsync(id)
    }
  }

  const handleCloseForm = () => {
    setShowForm(false)
    setEditingId(null)
  }

  if (showForm) {
    return <SupplierForm id={editingId} onClose={handleCloseForm} />
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Fornecedores</h2>
          <p className="text-muted-foreground">Gerenciamento de fornecedores e prestadores</p>
        </div>
        <Button onClick={() => setShowForm(true)}>Novo Fornecedor</Button>
      </div>

      <Card>
        <CardHeader>
          <Input
            placeholder="Buscar por nome, CNPJ..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="max-w-sm"
          />
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8 text-muted-foreground">Carregando...</div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nome</TableHead>
                  <TableHead>CNPJ/CPF</TableHead>
                  <TableHead>Telefone</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Ações</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data?.data?.map((supplier: any) => (
                  <TableRow key={supplier.id}>
                    <TableCell className="font-medium">{supplier.name}</TableCell>
                    <TableCell>{supplier.cnpj || supplier.cpf || '-'}</TableCell>
                    <TableCell>{supplier.phone || '-'}</TableCell>
                    <TableCell>{supplier.email || '-'}</TableCell>
                    <TableCell>
                      <Badge variant={supplier.is_active ? 'success' : 'secondary'}>
                        {supplier.is_active ? 'Ativo' : 'Inativo'}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right space-x-2">
                      <Button variant="outline" size="sm" onClick={() => handleEdit(supplier.id)}>
                        Editar
                      </Button>
                      <Button variant="destructive" size="sm" onClick={() => handleDelete(supplier.id)}>
                        Inativar
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
                {data.meta.total} fornecedor(es) encontrado(s)
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
