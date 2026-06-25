import { useState } from 'react'
import { usePayables, useDeletePayable, useApprovePayable, usePayPayable } from '../../hooks/usePayables'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Card, CardHeader, CardContent } from '../../components/ui/Card'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../../components/ui/Table'
import { Badge } from '../../components/ui/Badge'
import PayableForm from './PayableForm'

const statusMap: Record<string, { label: string; variant: string }> = {
  draft: { label: 'Rascunho', variant: 'secondary' },
  pending: { label: 'Pendente', variant: 'warning' },
  scheduled: { label: 'Agendado', variant: 'default' },
  paid: { label: 'Pago', variant: 'success' },
  cancelled: { label: 'Cancelado', variant: 'destructive' },
  overdue: { label: 'Atrasado', variant: 'destructive' },
}

export function PayablesPage() {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [page, setPage] = useState(1)
  const [showForm, setShowForm] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)
  const [showPayModal, setShowPayModal] = useState(false)
  const [payingId, setPayingId] = useState<number | null>(null)

  const { data, isLoading } = usePayables({ search, status: statusFilter || undefined, page, per_page: 15 })
  const deleteMutation = useDeletePayable()
  const approveMutation = useApprovePayable()
  const payMutation = usePayPayable()

  const handleEdit = (id: number) => {
    setEditingId(id)
    setShowForm(true)
  }

  const handleApprove = async (id: number) => {
    if (confirm('Confirma aprovação deste pagamento?')) {
      await approveMutation.mutateAsync(id)
    }
  }

  const handlePay = (id: number) => {
    setPayingId(id)
    setShowPayModal(true)
  }

  const handleConfirmPay = async (paymentData: any) => {
    if (payingId) {
      await payMutation.mutateAsync({ id: payingId, data: paymentData })
      setShowPayModal(false)
      setPayingId(null)
    }
  }

  const handleDelete = async (id: number) => {
    if (confirm('Confirma exclusão deste pagamento?')) {
      await deleteMutation.mutateAsync(id)
    }
  }

  const handleCloseForm = () => {
    setShowForm(false)
    setEditingId(null)
  }

  if (showForm) {
    return <PayableForm id={editingId} onClose={handleCloseForm} />
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Contas a Pagar</h2>
          <p className="text-muted-foreground">Gestão de pagamentos e fornecedores</p>
        </div>
        <Button onClick={() => setShowForm(true)}>Novo Pagamento</Button>
      </div>

      <Card>
        <CardHeader>
          <div className="flex gap-4">
            <Input
              placeholder="Buscar por descrição, documento..."
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
              <option value="pending">Pendente</option>
              <option value="overdue">Atrasado</option>
              <option value="paid">Pago</option>
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
                  <TableHead>Fornecedor</TableHead>
                  <TableHead>Vencimento</TableHead>
                  <TableHead>Valor</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Ações</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data?.data?.map((payable: any) => (
                  <TableRow key={payable.id}>
                    <TableCell className="font-medium">
                      {payable.description}
                      {payable.document_number && (
                        <span className="text-muted-foreground ml-2">({payable.document_number})</span>
                      )}
                    </TableCell>
                    <TableCell>{payable.supplier?.name || '-'}</TableCell>
                    <TableCell>
                      {payable.due_date ? new Date(payable.due_date).toLocaleDateString('pt-BR') : '-'}
                    </TableCell>
                    <TableCell>
                      R$ {Number(payable.amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                    </TableCell>
                    <TableCell>
                      <Badge variant={statusMap[payable.status]?.variant as any || 'secondary'}>
                        {statusMap[payable.status]?.label || payable.status}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right space-x-2">
                      {payable.status === 'draft' && (
                        <>
                          <Button variant="outline" size="sm" onClick={() => handleEdit(payable.id)}>
                            Editar
                          </Button>
                          <Button variant="default" size="sm" onClick={() => handleApprove(payable.id)}>
                            Aprovar
                          </Button>
                        </>
                      )}
                      {(payable.status === 'pending' || payable.status === 'overdue') && (
                        <Button variant="default" size="sm" onClick={() => handlePay(payable.id)}>
                          Registrar Pagamento
                        </Button>
                      )}
                      {payable.status !== 'cancelled' && (
                        <Button variant="destructive" size="sm" onClick={() => handleDelete(payable.id)}>
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
                {data.meta.total} pagamento(s) encontrado(s)
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

      {showPayModal && payingId && (
        <PayModal
          payable={data?.data?.find((p: any) => p.id === payingId)}
          onConfirm={handleConfirmPay}
          onClose={() => { setShowPayModal(false); setPayingId(null) }}
          isLoading={payMutation.isPending}
        />
      )}
    </div>
  )
}

function PayModal({ payable, onConfirm, onClose, isLoading }: {
  payable: any
  onConfirm: (data: any) => void
  onClose: () => void
  isLoading: boolean
}) {
  const [paymentData, setPaymentData] = useState({
    paid_amount: payable?.amount?.toString() || '',
    discount: '0',
    interest: '0',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'transfer',
    receipt_number: '',
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    onConfirm({
      ...paymentData,
      paid_amount: Number(paymentData.paid_amount),
      discount: Number(paymentData.discount),
      interest: Number(paymentData.interest),
    })
  }

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <Card className="w-full max-w-md">
        <CardHeader>
          <h3 className="text-lg font-semibold">Registrar Pagamento</h3>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium mb-1">Valor Original</label>
              <p className="text-lg font-bold">
                R$ {Number(payable?.amount || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
              </p>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Valor Pago *</label>
                <Input
                  type="number"
                  step="0.01"
                  value={paymentData.paid_amount}
                  onChange={(e) => setPaymentData({ ...paymentData, paid_amount: e.target.value })}
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Data Pagamento *</label>
                <Input
                  type="date"
                  value={paymentData.payment_date}
                  onChange={(e) => setPaymentData({ ...paymentData, payment_date: e.target.value })}
                  required
                />
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Desconto</label>
                <Input
                  type="number"
                  step="0.01"
                  value={paymentData.discount}
                  onChange={(e) => setPaymentData({ ...paymentData, discount: e.target.value })}
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Juros</label>
                <Input
                  type="number"
                  step="0.01"
                  value={paymentData.interest}
                  onChange={(e) => setPaymentData({ ...paymentData, interest: e.target.value })}
                />
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Forma de Pagamento</label>
                <select
                  value={paymentData.payment_method}
                  onChange={(e) => setPaymentData({ ...paymentData, payment_method: e.target.value })}
                  className="w-full border rounded-md px-3 py-2 text-sm"
                >
                  <option value="transfer">Transferência</option>
                  <option value="pix">PIX</option>
                  <option value="boleto">Boleto</option>
                  <option value="check">Cheque</option>
                  <option value="cash">Dinheiro</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Nº Comprovante</label>
                <Input
                  value={paymentData.receipt_number}
                  onChange={(e) => setPaymentData({ ...paymentData, receipt_number: e.target.value })}
                />
              </div>
            </div>

            <div className="flex justify-end gap-4 mt-6">
              <Button type="button" variant="outline" onClick={onClose}>
                Cancelar
              </Button>
              <Button type="submit" disabled={isLoading}>
                {isLoading ? 'Registrando...' : 'Confirmar Pagamento'}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}

export default PayablesPage
