import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { bankInvestmentService } from '@/services/bankInvestmentApi'
import { bankAccountService } from '@/services/bankAccounts'
import type { BankInvestment, BankAccount, PaginatedResponse } from '@/types'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/Select'
import { Card, CardContent } from '@/components/ui/Card'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/Table'
import { Badge } from '@/components/ui/Badge'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/Dialog'
import { Textarea } from '@/components/ui/Input'
import { Plus, Download, RefreshCw } from 'lucide-react'

function StatusBadge({ status }: { status: string }) {
  const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning'> = {
    active: 'success',
    redeemed: 'secondary',
    closed: 'destructive',
  }
  return <Badge variant={variants[status] || 'default'}>{status}</Badge>
}

function TypeBadge({ type }: { type: string }) {
  return <Badge variant="outline">{type.toUpperCase()}</Badge>
}

export function AplicacoesPage() {
  const queryClient = useQueryClient()
  const [showForm, setShowForm] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)
  const [formData, setFormData] = useState({
    bank_account_id: '',
    description: '',
    investment_type: 'aplicacao',
    amount: 0,
    yield_rate: 0,
    start_date: '',
    maturity_date: '',
    notes: '',
  })

  const { data, isLoading } = useQuery<PaginatedResponse<BankInvestment>>({
    queryKey: ['bank-investments'],
    queryFn: () => bankInvestmentService.list({ per_page: 100 }),
  })

  const { data: bankAccounts } = useQuery({
    queryKey: ['bank-accounts-all'],
    queryFn: () => bankAccountService.list({ per_page: 1000 }),
  })

  const createMutation = useMutation({
    mutationFn: (data: Partial<BankInvestment>) => bankInvestmentService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bank-investments'] })
      setShowForm(false)
      setEditingId(null)
      setFormData({ bank_account_id: '', description: '', investment_type: 'aplicacao', amount: 0, yield_rate: 0, start_date: '', maturity_date: '', notes: '' })
    },
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<BankInvestment> }) => bankInvestmentService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bank-investments'] })
      setShowForm(false)
      setEditingId(null)
    },
  })

  const redeemMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: { redeemed_amount: number; redeemed_at: string } }) => bankInvestmentService.redeem(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bank-investments'] })
    },
  })

  const handleOpenCreate = () => {
    setEditingId(null)
    setFormData({ bank_account_id: '', description: '', investment_type: 'aplicacao', amount: 0, yield_rate: 0, start_date: '', maturity_date: '', notes: '' })
    setShowForm(true)
  }

  const handleOpenEdit = (investment: BankInvestment) => {
    setEditingId(investment.id)
    setFormData({
      bank_account_id: investment.bank_account_id?.toString() || '',
      description: investment.description || '',
      investment_type: investment.type || 'aplicacao',
      amount: investment.initial_amount || investment.balance || 0,
      yield_rate: investment.rate || 0,
      start_date: investment.start_date || '',
      maturity_date: investment.maturity_date || '',
      notes: '',
    })
    setShowForm(true)
  }

  const handleSubmit = () => {
    const data = {
      bank_account_id: parseInt(formData.bank_account_id),
      description: formData.description,
      investment_type: formData.investment_type,
      amount: formData.amount,
      yield_rate: formData.yield_rate,
      start_date: formData.start_date || undefined,
      maturity_date: formData.maturity_date || undefined,
      notes: formData.notes || undefined,
    }
    if (editingId) {
      updateMutation.mutate({ id: editingId, data })
    } else {
      createMutation.mutate(data)
    }
  }

  const handleRedeem = async (investment: BankInvestment) => {
    const amount = prompt(`Valor a resgatar (saldo: ${investment.balance?.toLocaleString('pt-BR', { minimumFractionDigits: 2 })})`)
    if (amount) {
      const date = prompt('Data do resgate (YYYY-MM-DD)', new Date().toISOString().split('T')[0])
      if (date) {
        redeemMutation.mutate({ id: investment.id, data: { redeemed_amount: parseFloat(amount), redeemed_at: date } })
      }
    }
  }

  if (showForm) {
    return (
      <Dialog open={true} onOpenChange={open => !open && setShowForm(false)}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>{editingId ? 'Editar' : 'Nova'} Aplicacao</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <Select value={formData.bank_account_id} onValueChange={v => setFormData(p => ({ ...p, bank_account_id: v }))}>
              <SelectTrigger><SelectValue placeholder="Conta Bancaria" /></SelectTrigger>
              <SelectContent>
                {bankAccounts?.data?.map((acc: BankAccount) => (
                  <SelectItem key={acc.id} value={acc.id.toString()}>{acc.bank_name} - {acc.agency}/{acc.account}</SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Input placeholder="Descricao" value={formData.description} onChange={e => setFormData(p => ({ ...p, description: e.target.value }))} />
            <Select value={formData.investment_type} onValueChange={v => setFormData(p => ({ ...p, investment_type: v }))}>
              <SelectTrigger><SelectValue placeholder="Tipo" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="apl">APL</SelectItem>
                <SelectItem value="aplicacao">Aplicacao</SelectItem>
                <SelectItem value="investimento">Investimento</SelectItem>
                <SelectItem value="cdb">CDB</SelectItem>
                <SelectItem value="lci_lca">LCI/LCA</SelectItem>
              </SelectContent>
            </Select>
            <Input type="number" step="0.01" placeholder="Valor" value={formData.amount} onChange={e => setFormData(p => ({ ...p, amount: parseFloat(e.target.value) || 0 }))} />
            <Input type="number" step="0.0001" placeholder="Taxa Rendimento" value={formData.yield_rate} onChange={e => setFormData(p => ({ ...p, yield_rate: parseFloat(e.target.value) || 0 }))} />
            <Input type="date" placeholder="Data Inicio" value={formData.start_date} onChange={e => setFormData(p => ({ ...p, start_date: e.target.value }))} />
            <Input type="date" placeholder="Data Vencimento" value={formData.maturity_date} onChange={e => setFormData(p => ({ ...p, maturity_date: e.target.value }))} />
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
          <h2 className="text-2xl font-bold tracking-tight">Aplicacoes</h2>
          <p className="text-muted-foreground">Controle de aplicacoes e investimentos financeiros</p>
        </div>
        <div className="flex items-center gap-2">
          <Button variant="outline" size="sm" onClick={handleOpenCreate}>
            <Plus className="w-4 h-4 mr-2" /> Nova Aplicacao
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
                    <TableHead>Conta</TableHead>
                    <TableHead>Descricao</TableHead>
                    <TableHead>Tipo</TableHead>
                    <TableHead className="text-right">Valor Inicial</TableHead>
                    <TableHead className="text-right">Saldo Atual</TableHead>
                    <TableHead>Data Inicio</TableHead>
                    <TableHead>Vencimento</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-right">Acoes</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data?.data?.map((inv: BankInvestment) => (
                    <TableRow key={inv.id}>
                      <TableCell>{inv.bank_account?.bank_name || 'N/A'}</TableCell>
                      <TableCell className="font-medium">{inv.description}</TableCell>
                      <TableCell><TypeBadge type={inv.type} /></TableCell>
                      <TableCell className="text-right font-mono">{inv.initial_amount?.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</TableCell>
                      <TableCell className="text-right font-mono">{inv.balance?.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</TableCell>
                      <TableCell>{inv.start_date || '-'}</TableCell>
                      <TableCell>{inv.maturity_date || '-'}</TableCell>
                      <TableCell><StatusBadge status={inv.status} /></TableCell>
                      <TableCell className="text-right space-x-2">
                        <Button variant="outline" size="sm" onClick={() => handleOpenEdit(inv)}>Editar</Button>
                        {inv.status === 'active' && (
                          <Button variant="outline" size="sm" onClick={() => handleRedeem(inv)}>
                            <RefreshCw className="w-4 h-4 mr-2" /> Resgatar
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
                    {data.meta.total} aplicacao(oes) encontrada(s)
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
