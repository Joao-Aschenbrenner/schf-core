import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { receivableService } from '@/services/receivableApi'
import { supplierService } from '@/services/suppliers'
import type { Receivable, Supplier, PaginatedResponse } from '@/types'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/Select'
import { Card, CardContent } from '@/components/ui/Card'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/Table'
import { Badge } from '@/components/ui/Badge'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/Dialog'
import { Textarea } from '@/components/ui/Input'
import { Plus, Download, Check, DollarSign } from 'lucide-react'

function StatusBadge({ status }: { status: string }) {
  const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning'> = {
    pending: 'warning',
    received: 'success',
    cancelled: 'destructive',
    overdue: 'destructive',
  }
  return <Badge variant={variants[status] || 'default'}>{status}</Badge>
}

export function ReceivablesPage() {
  const queryClient = useQueryClient()
  const [showForm, setShowForm] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)
  const [formData, setFormData] = useState({
    supplier_id: '',
    description: '',
    amount: 0,
    discount: 0,
    interest: 0,
    due_date: '',
    payment_method: '',
    notes: '',
  })

  const { data, isLoading } = useQuery<PaginatedResponse<Receivable>>({
    queryKey: ['receivables'],
    queryFn: () => receivableService.list({ per_page: 100 }),
  })

  const { data: suppliers } = useQuery({
    queryKey: ['suppliers-all'],
    queryFn: () => supplierService.list({ per_page: 1000 }),
  })

  const createMutation = useMutation({
    mutationFn: (data: Partial<Receivable>) => receivableService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['receivables'] })
      setShowForm(false)
      setEditingId(null)
      setFormData({ supplier_id: '', description: '', amount: 0, discount: 0, interest: 0, due_date: '', payment_method: '', notes: '' })
    },
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<Receivable> }) => receivableService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['receivables'] })
      setShowForm(false)
      setEditingId(null)
    },
  })

  const approveMutation = useMutation({
    mutationFn: (id: number) => receivableService.approve(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['receivables'] }),
  })

  const receiveMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: { received_amount?: number; received_date?: string; payment_method?: string } }) => receivableService.receive(id, data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['receivables'] }),
  })

  const handleOpenCreate = () => {
    setEditingId(null)
    setFormData({ supplier_id: '', description: '', amount: 0, discount: 0, interest: 0, due_date: '', payment_method: '', notes: '' })
    setShowForm(true)
  }

  const handleOpenEdit = (rec: Receivable) => {
    setEditingId(rec.id)
    setFormData({
      supplier_id: rec.supplier_id?.toString() || '',
      description: rec.description || '',
      amount: rec.amount || 0,
      discount: rec.discount || 0,
      interest: rec.interest || 0,
      due_date: rec.due_date || '',
      payment_method: rec.payment_method || '',
      notes: rec.notes || '',
    })
    setShowForm(true)
  }

  const handleSubmit = () => {
    const data = {
      supplier_id: formData.supplier_id ? parseInt(formData.supplier_id) : undefined,
      description: formData.description,
      amount: formData.amount,
      discount: formData.discount,
      interest: formData.interest,
      due_date: formData.due_date,
      payment_method: formData.payment_method,
      notes: formData.notes,
    }
    if (editingId) {
      updateMutation.mutate({ id: editingId, data })
    } else {
      createMutation.mutate(data)
    }
  }

  const handleApprove = (id: number) => {
    if (confirm('Aprovar este recebimento?')) approveMutation.mutate(id)
  }

  const handleReceive = (rec: Receivable) => {
    const amount = prompt(`Valor recebido (total: ${rec.amount?.toLocaleString('pt-BR', { minimumFractionDigits: 2 })})`)
    const date = prompt('Data recebimento (YYYY-MM-DD)', new Date().toISOString().split('T')[0])
    const method = prompt('Forma pagamento (pix/boleto/transferencia/dinheiro)')
    if (amount && date) {
      receiveMutation.mutate({ id: rec.id, data: { received_amount: parseFloat(amount), received_date: date, payment_method: method || undefined } })
    }
  }

  if (showForm) {
    return (
      <Dialog open={true} onOpenChange={open => !open && setShowForm(false)}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>{editingId ? 'Editar' : 'Nova'} Conta a Receber</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <Select value={formData.supplier_id} onValueChange={v => setFormData(p => ({ ...p, supplier_id: v }))}>
              <SelectTrigger><SelectValue placeholder="Fornecedor" /></SelectTrigger>
              <SelectContent>
                {suppliers?.data?.map((s: Supplier) => (
                  <SelectItem key={s.id} value={s.id.toString()}>{s.name}</SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Input placeholder="Descricao" value={formData.description} onChange={e => setFormData(p => ({ ...p, description: e.target.value }))} />
            <Input type="number" step="0.01" placeholder="Valor" value={formData.amount} onChange={e => setFormData(p => ({ ...p, amount: parseFloat(e.target.value) || 0 }))} />
            <Input type="number" step="0.01" placeholder="Desconto" value={formData.discount} onChange={e => setFormData(p => ({ ...p, discount: parseFloat(e.target.value) || 0 }))} />
            <Input type="number" step="0.01" placeholder="Juros" value={formData.interest} onChange={e => setFormData(p => ({ ...p, interest: parseFloat(e.target.value) || 0 }))} />
            <Input type="date" placeholder="Vencimento" value={formData.due_date} onChange={e => setFormData(p => ({ ...p, due_date: e.target.value }))} />
            <Input placeholder="Forma Pagamento" value={formData.payment_method} onChange={e => setFormData(p => ({ ...p, payment_method: e.target.value }))} />
            <Textarea placeholder="Observacoes" value={formData.notes} onChange={e => setFormData(p => ({ ...p, notes: e.target.value }))} rows={3} />
            <div className="flex justify-end gap-2">
              <Button variant="outline" onClick={() => setShowForm(false)}>Cancelar</Button>
              <Button onClick={handleSubmit} disabled={createMutation.isPending || updateMutation.isPending}>
                {createMutation.isPending || updateMutation.isPending ? 'Salvando...' : 'Salvar'}
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    )
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Contas a Receber</h2>
          <p className="text-muted-foreground">Controle de recebimentos operacionais</p>
        </div>
        <div className="flex items-center gap-2">
          <Button variant="outline" size="sm" onClick={handleOpenCreate}>
            <Plus className="w-4 h-4 mr-2" /> Nova Recebivel
          </Button>
          <Button variant="outline" size="sm">
            <Download className="w-4 h-4 mr-2" /> CSV
          </Button>
          <Button variant="outline" size="sm">
            <Download className="w-4 h-4 mr-2" /> XLSX
          </Button>
        </div>
      </div>

      <Card>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8 text-muted-foreground">Carregando...</div>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Fornecedor</TableHead>
                    <TableHead>Descricao</TableHead>
                    <TableHead className="text-right">Valor</TableHead>
                    <TableHead>Vencimento</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-right">Acoes</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data?.data?.map((rec: Receivable) => (
                    <TableRow key={rec.id}>
                      <TableCell>{rec.supplier?.name || 'N/A'}</TableCell>
                      <TableCell className="font-medium">{rec.description}</TableCell>
                      <TableCell className="text-right font-mono">{rec.amount?.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</TableCell>
                      <TableCell>{rec.due_date || '-'}</TableCell>
                      <TableCell><StatusBadge status={rec.status} /></TableCell>
                      <TableCell className="text-right space-x-2">
                        <Button variant="outline" size="sm" onClick={() => handleOpenEdit(rec)}>Editar</Button>
                        {rec.status === 'pending' && (
                          <Button variant="outline" size="sm" onClick={() => handleApprove(rec.id)}>
                            <Check className="w-4 h-4 mr-2" /> Aprovar
                          </Button>
                        )}
                        {rec.status === 'pending' && (
                          <Button variant="outline" size="sm" onClick={() => handleReceive(rec)}>
                            <DollarSign className="w-4 h-4 mr-2" /> Receber
                          </Button>
                        )}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>

              {data?.meta && (
                <div className="flex items-center justify-between mt-4">
                  <p className="text-sm text-muted-foreground">
                    {data.meta.total} conta(s) a receber encontrada(s)
                  </p>
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
