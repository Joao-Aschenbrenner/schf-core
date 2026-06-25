import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { provisionService } from '@/services/provisionApi'
import type { Provision, Supplier, PaginatedResponse } from '@/types'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/Select'
import { Card, CardHeader, CardContent, CardTitle } from '@/components/ui/Card'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/Table'
import { Badge } from '@/components/ui/Badge'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/Dialog'
import { Textarea } from '@/components/ui/Input'
import { Plus, Download, Check, X, AlertCircle } from 'lucide-react'

function StatusBadge({ status }: { status: string }) {
  const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning'> = {
    draft: 'secondary',
    confirmed: 'warning',
    paid: 'success',
    cancelled: 'destructive',
  }
  return <Badge variant={variants[status] || 'default'}>{status}</Badge>
}

function TipoBadge({ tipo }: { tipo: string }) {
  return <Badge variant="outline">{tipo}</Badge>
}

export function ProvisoesPage() {
  const queryClient = useQueryClient()
  const [showForm, setShowForm] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)
  const [formData, setFormData] = useState({
    supplier_id: '',
    description: '',
    amount: 0,
    due_date: '',
    provision_type: '',
    notes: '',
  })

  const { data, isLoading } = useQuery<PaginatedResponse<Provision>>({
    queryKey: ['provisions'],
    queryFn: () => provisionService.list({ per_page: 100 }),
  })

  const { data: suppliers } = useQuery({
    queryKey: ['suppliers-all'],
    queryFn: () => provisionService.list?.({ per_page: 1000 }),
  })

  const createMutation = useMutation({
    mutationFn: (data: Partial<Provision>) => provisionService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['provisions'] })
      setShowForm(false)
      setEditingId(null)
      setFormData({ supplier_id: '', description: '', amount: 0, due_date: '', provision_type: '', notes: '' })
    },
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<Provision> }) => provisionService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['provisions'] })
      setShowForm(false)
      setEditingId(null)
    },
  })

  const confirmMutation = useMutation({
    mutationFn: (id: number) => provisionService.confirm(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['provisions'] }),
  })

  const payMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: { payment_date?: string; payment_method?: string } }) => provisionService.pay(id, data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['provisions'] }),
  })

  const cancelMutation = useMutation({
    mutationFn: (id: number) => provisionService.cancel(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['provisions'] }),
  })

  const handleOpenCreate = () => {
    setEditingId(null)
    setFormData({ supplier_id: '', description: '', amount: 0, due_date: '', provision_type: '', notes: '' })
    setShowForm(true)
  }

  const handleOpenEdit = (prov: Provision) => {
    setEditingId(prov.id)
    setFormData({
      supplier_id: prov.supplier_id?.toString() || '',
      description: prov.description || '',
      amount: prov.amount || 0,
      due_date: prov.due_date || '',
      provision_type: prov.tipo || '',
      notes: prov.notes || '',
    })
    setShowForm(true)
  }

  const handleSubmit = () => {
    const data = {
      supplier_id: formData.supplier_id ? parseInt(formData.supplier_id) : undefined,
      description: formData.description,
      amount: formData.amount,
      due_date: formData.due_date,
      provision_type: formData.provision_type,
      notes: formData.notes,
    }
    if (editingId) {
      updateMutation.mutate({ id: editingId, data })
    } else {
      createMutation.mutate(data)
    }
  }

  const handleConfirm = (id: number) => {
    if (confirm('Confirmar esta provisao?')) confirmMutation.mutate(id)
  }

  const handlePay = (prov: Provision) => {
    const date = prompt('Data pagamento (YYYY-MM-DD)', new Date().toISOString().split('T')[0])
    const method = prompt('Forma pagamento (pix/boleto/transferencia/dinheiro)')
    if (date) payMutation.mutate({ id: prov.id, data: { payment_date: date, payment_method: method || undefined } })
  }

  const handleCancel = (id: number) => {
    const reason = prompt('Motivo do cancelamento')
    if (reason) cancelMutation.mutate(id)
  }

  if (showForm) {
    return (
      <Dialog open={true} onOpenChange={open => !open && setShowForm(false)}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>{editingId ? 'Editar' : 'Nova'} Provisao</DialogTitle>
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
            <Input type="date" placeholder="Vencimento" value={formData.due_date} onChange={e => setFormData(p => ({ ...p, due_date: e.target.value }))} />
            <Input placeholder="Tipo" value={formData.provision_type} onChange={e => setFormData(p => ({ ...p, provision_type: e.target.value }))} />
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
          <h2 className="text-2xl font-bold tracking-tight">Provisoes</h2>
          <p className="text-muted-foreground">Controle de provisoes financeiras</p>
        </div>
        <div className="flex items-center gap-2">
          <Button variant="outline" size="sm" onClick={handleOpenCreate}>
            <Plus className="w-4 h-4 mr-2" /> Nova Provisao
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
                    <TableHead>Tipo</TableHead>
                    <TableHead className="text-right">Acoes</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data?.data?.map((prov: Provision) => (
                    <TableRow key={prov.id}>
                      <TableCell>{prov.supplier?.name || 'N/A'}</TableCell>
                      <TableCell className="font-medium">{prov.description}</TableCell>
                      <TableCell className="text-right font-mono">{prov.amount?.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</TableCell>
                      <TableCell>{prov.due_date || '-'}</TableCell>
                      <TableCell><StatusBadge status={prov.status} /></TableCell>
                      <TableCell><TipoBadge tipo={prov.tipo} /></TableCell>
                      <TableCell className="text-right space-x-2">
                        <Button variant="outline" size="sm" onClick={() => handleOpenEdit(prov)}>Editar</Button>
                        {prov.status === 'draft' && (
                          <Button variant="outline" size="sm" onClick={() => handleConfirm(prov.id)}>
                            <Check className="w-4 h-4 mr-2" /> Confirmar
                          </Button>
                        )}
                        {prov.status === 'confirmed' && (
                          <Button variant="outline" size="sm" onClick={() => handlePay(prov)}>
                            <DollarSign className="w-4 h-4 mr-2" /> Pagar
                          </Button>
                        )}
                        {prov.status !== 'cancelled' && (
                          <Button variant="destructive" size="sm" onClick={() => handleCancel(prov.id)}>
                            <X className="w-4 h-4 mr-2" /> Cancelar
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
                    {data.meta.total} provisao(oes) encontrada(s)
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