import { useState, useEffect } from 'react'
import { useNfe, useCreateNfe, useUpdateNfe } from '../../hooks/useNfes'
import { useSuppliers } from '../../hooks/useSuppliers'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Card, CardHeader, CardContent } from '../../components/ui/Card'

interface NfeFormProps {
  id: number | null
  onClose: () => void
}

export default function NfeForm({ id, onClose }: NfeFormProps) {
  const { data: nfe } = useNfe(id || 0)
  const createMutation = useCreateNfe()
  const updateMutation = useUpdateNfe()
  const { data: suppliersData } = useSuppliers({ per_page: 100 })

  const [formData, setFormData] = useState({
    nfe_number: '',
    nfe_key: '',
    serie: '',
    emission_date: new Date().toISOString().split('T')[0],
    supplier_id: '',
    description: '',
    goods_value: '',
    service_value: '',
    insurance_value: '',
    other_value: '',
    icms_value: '',
    ipi_value: '',
    pis_value: '',
    cofins_value: '',
    total_value: '',
    status: 'draft',
  })

  useEffect(() => {
    if (nfe) {
      setFormData({
        nfe_number: nfe.nfe_number || '',
        nfe_key: nfe.nfe_key || '',
        serie: nfe.serie || '',
        emission_date: nfe.emission_date || '',
        supplier_id: nfe.supplier_id?.toString() || '',
        description: nfe.description || '',
        goods_value: nfe.goods_value?.toString() || '',
        service_value: nfe.service_value?.toString() || '',
        insurance_value: nfe.insurance_value?.toString() || '',
        other_value: nfe.other_value?.toString() || '',
        icms_value: nfe.icms_value?.toString() || '',
        ipi_value: nfe.ipi_value?.toString() || '',
        pis_value: nfe.pis_value?.toString() || '',
        cofins_value: nfe.cofins_value?.toString() || '',
        total_value: nfe.total_value?.toString() || '',
        status: nfe.status || 'draft',
      })
    }
  }, [nfe])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    const submitData = {
      ...formData,
      supplier_id: formData.supplier_id ? Number(formData.supplier_id) : null,
      goods_value: formData.goods_value ? Number(formData.goods_value) : 0,
      service_value: formData.service_value ? Number(formData.service_value) : 0,
      insurance_value: formData.insurance_value ? Number(formData.insurance_value) : 0,
      other_value: formData.other_value ? Number(formData.other_value) : 0,
      icms_value: formData.icms_value ? Number(formData.icms_value) : 0,
      ipi_value: formData.ipi_value ? Number(formData.ipi_value) : 0,
      pis_value: formData.pis_value ? Number(formData.pis_value) : 0,
      cofins_value: formData.cofins_value ? Number(formData.cofins_value) : 0,
      total_value: formData.total_value ? Number(formData.total_value) : 0,
      status: formData.status as 'draft' | 'confirmed' | 'cancelled',
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
            {id ? 'Editar NF-e' : 'Nova NF-e'}
          </h2>
          <p className="text-muted-foreground">
            {id ? 'Atualize os dados da nota fiscal' : 'Preencha os dados da nova nota fiscal'}
          </p>
        </div>
        <Button variant="outline" onClick={onClose}>Voltar</Button>
      </div>

      <form onSubmit={handleSubmit}>
        <Card>
          <CardHeader>
            <h3 className="text-lg font-semibold">Dados Gerais</h3>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Número NF-e *</label>
                <Input
                  name="nfe_number"
                  value={formData.nfe_number}
                  onChange={handleChange}
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Chave de Acesso</label>
                <Input
                  name="nfe_key"
                  value={formData.nfe_key}
                  onChange={handleChange}
                  maxLength={44}
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Série</label>
                <Input
                  name="serie"
                  value={formData.serie}
                  onChange={handleChange}
                />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Data de Emissão *</label>
                <Input
                  type="date"
                  name="emission_date"
                  value={formData.emission_date}
                  onChange={handleChange}
                  required
                />
              </div>
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
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">Descrição</label>
              <textarea
                name="description"
                value={formData.description}
                onChange={handleChange}
                rows={2}
                className="w-full border rounded-md px-3 py-2 text-sm"
              />
            </div>
          </CardContent>
        </Card>

        <Card className="mt-4">
          <CardHeader>
            <h3 className="text-lg font-semibold">Valores</h3>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Valor Produtos</label>
                <Input
                  type="number"
                  step="0.01"
                  name="goods_value"
                  value={formData.goods_value}
                  onChange={handleChange}
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Valor Serviços</label>
                <Input
                  type="number"
                  step="0.01"
                  name="service_value"
                  value={formData.service_value}
                  onChange={handleChange}
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Valor Seguro</label>
                <Input
                  type="number"
                  step="0.01"
                  name="insurance_value"
                  value={formData.insurance_value}
                  onChange={handleChange}
                />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">ICMS</label>
                <Input
                  type="number"
                  step="0.01"
                  name="icms_value"
                  value={formData.icms_value}
                  onChange={handleChange}
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">IPI</label>
                <Input
                  type="number"
                  step="0.01"
                  name="ipi_value"
                  value={formData.ipi_value}
                  onChange={handleChange}
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">PIS</label>
                <Input
                  type="number"
                  step="0.01"
                  name="pis_value"
                  value={formData.pis_value}
                  onChange={handleChange}
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">COFINS</label>
                <Input
                  type="number"
                  step="0.01"
                  name="cofins_value"
                  value={formData.cofins_value}
                  onChange={handleChange}
                />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">Outros Valores</label>
                <Input
                  type="number"
                  step="0.01"
                  name="other_value"
                  value={formData.other_value}
                  onChange={handleChange}
                />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Valor Total *</label>
                <Input
                  type="number"
                  step="0.01"
                  name="total_value"
                  value={formData.total_value}
                  onChange={handleChange}
                  required
                />
              </div>
            </div>
          </CardContent>
        </Card>

        <div className="flex justify-end gap-4 mt-6">
          <Button type="button" variant="outline" onClick={onClose}>
            Cancelar
          </Button>
          <Button type="submit" disabled={isLoading}>
            {isLoading ? 'Salvando...' : id ? 'Atualizar' : 'Criar NF-e'}
          </Button>
        </div>
      </form>
    </div>
  )
}
