import { useState, useEffect } from 'react'
import { usePayable, useCreatePayable, useUpdatePayable } from '../../hooks/usePayables'
import { useSuppliers } from '../../hooks/useSuppliers'
import { useHealthPlans } from '../../hooks/useHealthPlans'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Card, CardHeader, CardContent } from '../../components/ui/Card'

interface PayableFormProps {
  id: number | null
  onClose: () => void
}

export default function PayableForm({ id, onClose }: PayableFormProps) {
  const { data: payable } = usePayable(id || 0)
  const createMutation = useCreatePayable()
  const updateMutation = useUpdatePayable()
  const { data: suppliersData } = useSuppliers({ per_page: 100 })
  const { data: healthPlansData } = useHealthPlans({ per_page: 100 })

  const [formData, setFormData] = useState({
    description: '',
    document_number: '',
    supplier_id: '',
    health_plan_id: '',
    amount: '',
    discount: '0',
    interest: '0',
    due_date: new Date().toISOString().split('T')[0],
    payment_method: '',
    bar_code: '',
    notes: '',
    status: 'pending',
  })

  useEffect(() => {
    if (payable) {
      setFormData({
        description: payable.description || '',
        document_number: payable.document_number || '',
        supplier_id: payable.supplier_id?.toString() || '',
        health_plan_id: payable.health_plan_id?.toString() || '',
        amount: payable.amount?.toString() || '',
        discount: payable.discount?.toString() || '0',
        interest: payable.interest?.toString() || '0',
        due_date: payable.due_date || '',
        payment_method: payable.payment_method || '',
        bar_code: payable.bar_code || '',
        notes: payable.notes || '',
        status: payable.status || 'pending',
      })
    }
  }, [payable])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    const submitData = {
      ...formData,
      supplier_id: formData.supplier_id ? Number(formData.supplier_id) : null,
      health_plan_id: formData.health_plan_id ? Number(formData.health_plan_id) : null,
      amount: Number(formData.amount),
      discount: Number(formData.discount),
      interest: Number(formData.interest),
      status: formData.status as 'draft' | 'pending' | 'scheduled' | 'paid' | 'cancelled' | 'overdue',
    }

    if (id) {
      await updateMutation.mutateAsync({ id, data: submitData })
    } else {
      await createMutation.mutateAsync(submitData)
    }

    onClose()
  }

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    setFormData({ ...formData, [e.target.name]: e.target.value })
  }

  const isLoading = createMutation.isPending || updateMutation.isPending

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">
            {id ? 'Editar Pagamento' : 'Novo Pagamento'}
          </h2>
          <p className="text-muted-foreground">
            {id ? 'Atualize os dados do pagamento' : 'Preencha os dados do novo pagamento'}
          </p>
        </div>
        <Button variant="outline" onClick={onClose}>Voltar</Button>
      </div>

      <form onSubmit={handleSubmit}>
        <Card>
          <CardHeader>
            <h3 className="text-lg font-semibold">Dados do Pagamento</h3>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Descrição *</label>
                <Input
                  name="description"
                  value={formData.description}
                  onChange={handleChange}
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Nº Documento</label>
                <Input
                  name="document_number"
                  value={formData.document_number}
                  onChange={handleChange}
                />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Fornecedor</label>
                <select
                  name="supplier_id"
                  value={formData.supplier_id}
                  onChange={handleChange}
                  className="w-full border rounded-md px-3 py-2 text-sm"
                >
                  <option value="">Selecione...</option>
                  {suppliersData?.data?.map((s: any) => (
                    <option key={s.id} value={s.id}>{s.name}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Convênio</label>
                <select
                  name="health_plan_id"
                  value={formData.health_plan_id}
                  onChange={handleChange}
                  className="w-full border rounded-md px-3 py-2 text-sm"
                >
                  <option value="">Selecione...</option>
                  {healthPlansData?.data?.map((h: any) => (
                    <option key={h.id} value={h.id}>{h.name}</option>
                  ))}
                </select>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Valor *</label>
                <Input
                  type="number"
                  step="0.01"
                  name="amount"
                  value={formData.amount}
                  onChange={handleChange}
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Data Vencimento *</label>
                <Input
                  type="date"
                  name="due_date"
                  value={formData.due_date}
                  onChange={handleChange}
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Forma de Pagamento</label>
                <select
                  name="payment_method"
                  value={formData.payment_method}
                  onChange={handleChange}
                  className="w-full border rounded-md px-3 py-2 text-sm"
                >
                  <option value="">Selecione...</option>
                  <option value="transfer">Transferência</option>
                  <option value="pix">PIX</option>
                  <option value="boleto">Boleto</option>
                  <option value="check">Cheque</option>
                  <option value="cash">Dinheiro</option>
                </select>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Código de Barras</label>
                <Input
                  name="bar_code"
                  value={formData.bar_code}
                  onChange={handleChange}
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Status</label>
                <select
                  name="status"
                  value={formData.status}
                  onChange={handleChange}
                  className="w-full border rounded-md px-3 py-2 text-sm"
                >
                  <option value="draft">Rascunho</option>
                  <option value="pending">Pendente</option>
                  <option value="scheduled">Agendado</option>
                </select>
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">Observações</label>
              <textarea
                name="notes"
                value={formData.notes}
                onChange={handleChange}
                rows={3}
                className="w-full border rounded-md px-3 py-2 text-sm"
              />
            </div>
          </CardContent>
        </Card>

        <div className="flex justify-end gap-4 mt-6">
          <Button type="button" variant="outline" onClick={onClose}>
            Cancelar
          </Button>
          <Button type="submit" disabled={isLoading}>
            {isLoading ? 'Salvando...' : id ? 'Atualizar' : 'Criar Pagamento'}
          </Button>
        </div>
      </form>
    </div>
  )
}
