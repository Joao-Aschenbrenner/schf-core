import { useState } from 'react'
import { useCreateSupplier, useUpdateSupplier } from '../../hooks/useSuppliers'
import { useSupplier } from '../../hooks/useSuppliers'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Label } from '../../components/ui/Label'
import { Card, CardContent } from '../../components/ui/Card'

interface SupplierFormProps {
  id?: number | null
  onClose: () => void
}

export default function SupplierForm({ id, onClose }: SupplierFormProps) {
  const { data: supplier } = useSupplier(id || 0)
  const createMutation = useCreateSupplier()
  const updateMutation = useUpdateSupplier()

  const [form, setForm] = useState({
    name: supplier?.name || '',
    cnpj: supplier?.cnpj || '',
    cpf: supplier?.cpf || '',
    trade_name: supplier?.trade_name || '',
    ie: supplier?.ie || '',
    email: supplier?.email || '',
    phone: supplier?.phone || '',
    cellphone: supplier?.cellphone || '',
    contact_name: supplier?.contact_name || '',
    address_street: supplier?.address_street || '',
    address_number: supplier?.address_number || '',
    address_district: supplier?.address_district || '',
    address_city: supplier?.address_city || '',
    address_state: supplier?.address_state || '',
    address_zip: supplier?.address_zip || '',
    bank_name: supplier?.bank_name || '',
    bank_agency: supplier?.bank_agency || '',
    bank_account: supplier?.bank_account || '',
    pix_key: supplier?.pix_key || '',
    pix_type: supplier?.pix_type || '',
    notes: supplier?.notes || '',
  })

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (id) {
      await updateMutation.mutateAsync({ id, data: form })
    } else {
      await createMutation.mutateAsync(form)
    }

    onClose()
  }

  const updateField = (field: string, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }))
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-2xl font-bold tracking-tight">
          {id ? 'Editar Fornecedor' : 'Novo Fornecedor'}
        </h2>
        <Button variant="outline" onClick={onClose}>Voltar</Button>
      </div>

      <Card>
        <CardContent className="pt-6">
          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="name">Nome *</Label>
                <Input id="name" value={form.name} onChange={(e) => updateField('name', e.target.value)} required />
              </div>
              <div className="space-y-2">
                <Label htmlFor="cnpj">CNPJ</Label>
                <Input id="cnpj" value={form.cnpj} onChange={(e) => updateField('cnpj', e.target.value)} maxLength={14} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="cpf">CPF</Label>
                <Input id="cpf" value={form.cpf} onChange={(e) => updateField('cpf', e.target.value)} maxLength={11} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="trade_name">Nome Fantasia</Label>
                <Input id="trade_name" value={form.trade_name} onChange={(e) => updateField('trade_name', e.target.value)} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="email">E-mail</Label>
                <Input id="email" type="email" value={form.email} onChange={(e) => updateField('email', e.target.value)} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="phone">Telefone</Label>
                <Input id="phone" value={form.phone} onChange={(e) => updateField('phone', e.target.value)} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="cellphone">Celular</Label>
                <Input id="cellphone" value={form.cellphone} onChange={(e) => updateField('cellphone', e.target.value)} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="ie">Inscrição Estadual</Label>
                <Input id="ie" value={form.ie} onChange={(e) => updateField('ie', e.target.value)} />
              </div>
            </div>

            <div className="border-t pt-4">
              <h3 className="font-semibold mb-4">Endereço</h3>
              <div className="grid gap-4 md:grid-cols-3">
                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="address_street">Rua</Label>
                  <Input id="address_street" value={form.address_street} onChange={(e) => updateField('address_street', e.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="address_number">Número</Label>
                  <Input id="address_number" value={form.address_number} onChange={(e) => updateField('address_number', e.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="address_district">Bairro</Label>
                  <Input id="address_district" value={form.address_district} onChange={(e) => updateField('address_district', e.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="address_city">Cidade</Label>
                  <Input id="address_city" value={form.address_city} onChange={(e) => updateField('address_city', e.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="address_state">UF</Label>
                  <Input id="address_state" value={form.address_state} onChange={(e) => updateField('address_state', e.target.value)} maxLength={2} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="address_zip">CEP</Label>
                  <Input id="address_zip" value={form.address_zip} onChange={(e) => updateField('address_zip', e.target.value)} maxLength={9} />
                </div>
              </div>
            </div>

            <div className="border-t pt-4">
              <h3 className="font-semibold mb-4">Dados Bancários</h3>
              <div className="grid gap-4 md:grid-cols-3">
                <div className="space-y-2">
                  <Label htmlFor="bank_name">Banco</Label>
                  <Input id="bank_name" value={form.bank_name} onChange={(e) => updateField('bank_name', e.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="bank_agency">Agência</Label>
                  <Input id="bank_agency" value={form.bank_agency} onChange={(e) => updateField('bank_agency', e.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="bank_account">Conta</Label>
                  <Input id="bank_account" value={form.bank_account} onChange={(e) => updateField('bank_account', e.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="pix_key">Chave PIX</Label>
                  <Input id="pix_key" value={form.pix_key} onChange={(e) => updateField('pix_key', e.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="pix_type">Tipo PIX</Label>
                  <select id="pix_type" value={form.pix_type} onChange={(e) => updateField('pix_type', e.target.value)}
                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    <option value="">Selecione</option>
                    <option value="cpf">CPF</option>
                    <option value="cnpj">CNPJ</option>
                    <option value="email">E-mail</option>
                    <option value="phone">Telefone</option>
                    <option value="random">Aleatória</option>
                  </select>
                </div>
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="notes">Observações</Label>
              <textarea id="notes" value={form.notes} onChange={(e) => updateField('notes', e.target.value)}
                className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                rows={3} />
            </div>

            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={onClose}>Cancelar</Button>
              <Button type="submit" disabled={createMutation.isPending || updateMutation.isPending}>
                {createMutation.isPending || updateMutation.isPending ? 'Salvando...' : 'Salvar'}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
