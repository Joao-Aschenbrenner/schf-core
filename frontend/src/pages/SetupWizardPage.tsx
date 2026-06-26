import React, { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Building2, UserCheck, CheckCircle, Loader2, AlertCircle } from 'lucide-react'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Label } from '@/components/ui/Label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { setupApi, OrganizationData, AdminData } from '@/services/setupApi'

type Step = 1 | 2 | 3

export function SetupWizardPage() {
  const navigate = useNavigate()
  const [step, setStep] = useState<Step>(1)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const [orgData, setOrgData] = useState<OrganizationData>({
    name: '',
    cnpj: '',
    city: '',
    state: '',
    email: '',
    phone: '',
    address: '',
  })

  const [adminData, setAdminData] = useState<AdminData>({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  })

  const handleOrgSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)
    setLoading(true)

    try {
      await setupApi.createOrganization(orgData)
      setStep(2)
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erro ao criar organização')
    } finally {
      setLoading(false)
    }
  }

  const handleAdminSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)
    setLoading(true)

    try {
      await setupApi.createAdmin(adminData)
      setStep(3)
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erro ao criar administrador')
    } finally {
      setLoading(false)
    }
  }

  const handleComplete = async () => {
    setLoading(true)
    try {
      await setupApi.complete()
      navigate('/login')
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erro ao finalizar configuração')
    } finally {
      setLoading(false)
    }
  }

  const formatCNPJ = (value: string) => {
    const numbers = value.replace(/\D/g, '')
    if (numbers.length <= 14) {
      return numbers
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d)/, '$1-$2')
    }
    return value
  }

  const steps = [
    { number: 1, label: 'Instituição', icon: Building2 },
    { number: 2, label: 'Administrador', icon: UserCheck },
    { number: 3, label: 'Concluído', icon: CheckCircle },
  ]

  return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-gray-900">SCHF</h1>
          <p className="text-gray-500 mt-2">Sistema de Controle Hospitalar e Financeiro</p>
        </div>

        <div className="mb-8 flex justify-center">
          <div className="flex items-center">
            {steps.map((s, i) => (
              <React.Fragment key={s.number}>
                <div className="flex items-center">
                  <div
                    className={`w-10 h-10 rounded-full flex items-center justify-center ${
                      step >= s.number ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-400'
                    }`}
                  >
                    {step > s.number ? <CheckCircle className="w-5 h-5" /> : <s.icon className="w-5 h-5" />}
                  </div>
                  <span className={`ml-2 text-sm font-medium ${step >= s.number ? 'text-gray-900' : 'text-gray-400'}`}>
                    {s.label}
                  </span>
                </div>
                {i < steps.length - 1 && (
                  <div className={`w-16 h-0.5 mx-2 ${step > s.number ? 'bg-blue-600' : 'bg-gray-200'}`} />
                )}
              </React.Fragment>
            ))}
          </div>
        </div>

        <Card>
          <CardHeader>
            <CardTitle className="text-2xl">Configuração Inicial</CardTitle>
          </CardHeader>
          <CardContent className="space-y-6">
            {error && (
              <div className="p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2 text-red-700">
                <AlertCircle className="w-5 h-5 flex-shrink-0" />
                <span>{error}</span>
              </div>
            )}

            {step === 1 && (
              <form onSubmit={handleOrgSubmit} className="space-y-4">
                <div>
                  <Label htmlFor="name">Nome da Instituição *</Label>
                  <Input
                    id="name"
                    value={orgData.name}
                    onChange={(e) => setOrgData({ ...orgData, name: e.target.value })}
                    placeholder="Ex: Hospital Geral"
                    required
                    disabled={loading}
                  />
                </div>
                <div>
                  <Label htmlFor="cnpj">CNPJ</Label>
                  <Input
                    id="cnpj"
                    value={orgData.cnpj}
                    onChange={(e) => setOrgData({ ...orgData, cnpj: formatCNPJ(e.target.value) })}
                    placeholder="00.000.000/0000-00"
                    maxLength={18}
                    disabled={loading}
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <Label htmlFor="city">Cidade</Label>
                    <Input
                      id="city"
                      value={orgData.city}
                      onChange={(e) => setOrgData({ ...orgData, city: e.target.value })}
                      disabled={loading}
                    />
                  </div>
                  <div>
                    <Label htmlFor="state">UF</Label>
                    <Input
                      id="state"
                      value={orgData.state}
                      onChange={(e) => setOrgData({ ...orgData, state: e.target.value.toUpperCase() })}
                      maxLength={2}
                      placeholder="SP"
                      disabled={loading}
                    />
                  </div>
                </div>
                <div>
                  <Label htmlFor="email">E-mail de Contato</Label>
                  <Input
                    id="email"
                    type="email"
                    value={orgData.email}
                    onChange={(e) => setOrgData({ ...orgData, email: e.target.value })}
                    placeholder="contato@hospital.com"
                    disabled={loading}
                  />
                </div>
                <div>
                  <Label htmlFor="phone">Telefone</Label>
                  <Input
                    id="phone"
                    value={orgData.phone}
                    onChange={(e) => setOrgData({ ...orgData, phone: e.target.value })}
                    placeholder="(11) 99999-9999"
                    disabled={loading}
                  />
                </div>
                <div>
                  <Label htmlFor="address">Endereço</Label>
                  <Input
                    id="address"
                    value={orgData.address}
                    onChange={(e) => setOrgData({ ...orgData, address: e.target.value })}
                    placeholder="Rua Exemplo, 123 - Centro"
                    disabled={loading}
                  />
                </div>
                <Button type="submit" className="w-full" disabled={loading}>
                  {loading ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : null}
                  Próximo
                </Button>
              </form>
            )}

            {step === 2 && (
              <form onSubmit={handleAdminSubmit} className="space-y-4">
                <div>
                  <Label htmlFor="adminName">Nome Completo *</Label>
                  <Input
                    id="adminName"
                    value={adminData.name}
                    onChange={(e) => setAdminData({ ...adminData, name: e.target.value })}
                    placeholder="João Silva"
                    required
                    disabled={loading}
                  />
                </div>
                <div>
                  <Label htmlFor="adminEmail">E-mail *</Label>
                  <Input
                    id="adminEmail"
                    type="email"
                    value={adminData.email}
                    onChange={(e) => setAdminData({ ...adminData, email: e.target.value })}
                    placeholder="admin@hospital.com"
                    required
                    disabled={loading}
                  />
                </div>
                <div>
                  <Label htmlFor="password">Senha *</Label>
                  <Input
                    id="password"
                    type="password"
                    value={adminData.password}
                    onChange={(e) => setAdminData({ ...adminData, password: e.target.value })}
                    required
                    minLength={8}
                    disabled={loading}
                  />
                </div>
                <div>
                  <Label htmlFor="password_confirmation">Confirmar Senha *</Label>
                  <Input
                    id="password_confirmation"
                    type="password"
                    value={adminData.password_confirmation}
                    onChange={(e) => setAdminData({ ...adminData, password_confirmation: e.target.value })}
                    required
                    disabled={loading}
                  />
                </div>
                <Button type="submit" className="w-full" disabled={loading}>
                  {loading ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : null}
                  {step === 2 ? 'Criar Administrador' : 'Próximo'}
                </Button>
              </form>
            )}

            {step === 3 && (
              <div className="text-center space-y-4">
                <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                  <CheckCircle className="w-8 h-8 text-green-600" />
                </div>
                <h3 className="text-xl font-semibold text-gray-900">Configuração Concluída!</h3>
                <p className="text-gray-500">
                  A instituição <strong>{orgData.name}</strong> foi configurada com sucesso.
                  O administrador <strong>{adminData.name}</strong> foi criado com permissões de Master Admin.
                </p>
                <Button onClick={handleComplete} className="w-full" disabled={loading}>
                  {loading ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : null}
                  Ir para Login
                </Button>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}