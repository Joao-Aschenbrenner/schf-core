import { useState, useEffect } from 'react'
import { usePreLaunch, useCreatePreLaunch, useUpdatePreLaunch } from '../../hooks/usePreLaunches'
import { useSuppliers } from '../../hooks/useSuppliers'
import { useHealthPlans } from '../../hooks/useHealthPlans'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Card, CardHeader, CardContent } from '../../components/ui/Card'

interface PreLaunchFormProps {
  id: number | null
  onClose: () => void
}

export default function PreLaunchForm({ id, onClose }: PreLaunchFormProps) {
  const { data: preLaunch } = usePreLaunch(id || 0)
  const createMutation = useCreatePreLaunch()
  const updateMutation = useUpdatePreLaunch()
  const { data: suppliersData } = useSuppliers({ per_page: 100 })
  const { data: healthPlansData } = useHealthPlans({ per_page: 100 })

  const [formData, setFormData] = useState({
    description: '',
    type: 'supplier',
    supplier_id: '',
    health_plan_id: '',
    amount: '',
    due_date: new Date().toISOString().split('T')[0],
    notes: '',
    status: 'draft',
  })

  useEffect(() => {
    if (preLaunch) {
      setFormData({
        description: preLaunch.description || '',
        type: preLaunch.type || 'supplier',
        supplier_id: preLaunch.supplier_id?.toString() || '',
        health_plan_id: preLaunch.health_plan_id?.toString() || '',
        amount: preLaunch.amount?.toString() || '',
        due_date: preLaunch.due_date || '',
        notes: preLaunch.notes || '',
        status: preLaunch.status || 'draft',
      })
    }
  }, [preLaunch])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    const submitData = {
      ...formData,
      supplier_id: formData.supplier_id ? Number(formData.supplier_id) : null,
      health_plan_id: formData.health_plan_id ? Number(formData.health_plan_id) : null,
      amount: Number(formData.amount),
      type: formData.type as 'payroll' | 'medical_fees' | 'tax' | 'supplier' | 'recurring',
      status: formData.status as 'draft' | 'confirmed' | 'converted' | 'cancelled',
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
            {id ? 'Editar Pré-Lançamento' : 'Novo Pré-Lançamento'}
          </h2>
          <p className="text-muted-foreground">
            {id ? 'Atualize os dados do pré-lançamento' : 'Preencha os dados do novo pré-lançamento'}
          </p>
        </div>
        <Button variant="outline" onClick={onClose}>Voltar</Button>
      </div>

      <form onSubmit={handleSubmit}>
        <Card>
          <CardHeader>
            <h3 className="text-lg font-semibold">Dados do Pré-Lançamento</h3>
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
                <label className="block text-sm font-medium mb-1">Tipo *</label>
                <select
                  name="type"
                  value={formData.type}
                  onChange={handleChange}
                  className="w-full border rounded-md px-3 py-2 text-sm"
                  required
                >
                  <option value="supplier">Fornecedores</option>
                  <option value="payroll">Folha de Pagamento</option>
                  <option value="medical_fees">Honorários Médicos</option>
                  <option value="tax">Impostos</option>
                  <option value="recurring">Despesas Recorrentes</option>
                </select>
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

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
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
            {isLoading ? 'Salvando...' : id ? 'Atualizar' : 'Criar Pré-Lançamento'}
          </Button>
        </div>
      </form>
    </div>
  )
}
