import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { cashRegisterService, cashMovementService } from '@/services/cashRegisterApi'
import type { CashRegister, CashMovement } from '@/types'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/Select'
import { Card, CardHeader, CardContent, CardTitle } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/Tabs'
import { Textarea } from '@/components/ui/Input'
import { Plus, Check, Download } from 'lucide-react'
import { format } from 'date-fns'

export function CaixaInternoPage() {
  const queryClient = useQueryClient()
  const [activeTab, setActiveTab] = useState<'abertura' | 'fechamento' | 'lancamento' | 'extrato'>('abertura')
  const [periodo, setPeriodo] = useState({
    data_inicio: format(new Date(), 'yyyy-MM-dd'),
    data_fim: format(new Date(), 'yyyy-MM-dd'),
  })

  const { data: registers } = useQuery({
    queryKey: ['cash-registers', periodo],
    queryFn: () => cashRegisterService.list({ ...periodo, per_page: 1000 }),
  })

  const openRegisterMutation = useMutation({
    mutationFn: (data: { date: string; opening_balance: number }) => cashRegisterService.open(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cash-registers'] })
    },
  })

  const closeRegister = useMutation({
    mutationFn: ({ id, data }: { id: number; data?: { closing_balance?: number } }) => cashRegisterService.close(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cash-registers'] })
    },
  })

  const addMovement = useMutation({
    mutationFn: (data: {
      cash_register_id: number
      type: 'credit' | 'debit'
      amount: number
      description: string
      category?: string
      payment_method?: string
      document?: string
    }) => cashMovementService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cash-registers'] })
    },
  })

  const [openRegisterForm, setOpenRegisterForm] = useState({ date: format(new Date(), 'yyyy-MM-dd'), opening_balance: 0 })
  const [movementForm, setMovementForm] = useState({ type: 'credit' as 'credit' | 'debit', amount: 0, description: '', category: '', payment_method: '', document: '' })

  const openRegisterHandle = () => {
    openRegisterMutation.mutate(openRegisterForm)
  }

  const closeRegisterHandle = (register: CashRegister) => {
    closeRegister.mutate({ id: register.id, data: { closing_balance: register.closing_balance || undefined } })
  }

  const addMovementHandle = (register: CashRegister) => {
    addMovement.mutate({ ...movementForm, cash_register_id: register.id })
    setMovementForm({ type: 'credit', amount: 0, description: '', category: '', payment_method: '', document: '' })
  }

  const currentOpenRegister = registers?.data?.find(r => r.status === 'open')

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Caixa Interno</h2>
          <p className="text-muted-foreground">Controle diario de abertura, fechamento e movimentacoes</p>
        </div>
        <div className="flex items-center gap-2">
          <Button variant="outline" size="sm">
            <Download className="w-4 h-4 mr-2" /> CSV
          </Button>
          <Button variant="outline" size="sm">
            <Download className="w-4 h-4 mr-2" /> XLSX
          </Button>
        </div>
      </div>

      <Tabs value={activeTab} onValueChange={value => setActiveTab(value as typeof activeTab)} className="w-full">
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="abertura">Abertura</TabsTrigger>
          <TabsTrigger value="fechamento">Fechamento</TabsTrigger>
          <TabsTrigger value="lancamento">Lancamento</TabsTrigger>
          <TabsTrigger value="extrato">Extrato</TabsTrigger>
        </TabsList>

        <TabsContent value="abertura">
          <Card className="mt-4">
            <CardHeader><CardTitle>Abertura de Caixa</CardTitle></CardHeader>
            <CardContent>
              {currentOpenRegister ? (
                <div className="space-y-4">
                  <div className="p-4 bg-green-50 rounded-lg border border-green-200">
                    <p className="font-medium text-green-800">Caixa ja aberto hoje</p>
                    <p className="text-sm text-green-700">Aberto por {currentOpenRegister.operator} as {currentOpenRegister.date}</p>
                    <p className="text-sm text-green-700">Saldo abertura: {currentOpenRegister.opening_balance?.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
                  </div>
                </div>
              ) : (
                <div className="space-y-4 max-w-md">
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="text-sm text-muted-foreground">Data</label>
                      <Input
                        type="date"
                        value={openRegisterForm.date}
                        onChange={e => setOpenRegisterForm(p => ({ ...p, date: e.target.value }))}
                      />
                    </div>
                    <div>
                      <label className="text-sm text-muted-foreground">Saldo Abertura</label>
                      <Input
                        type="number"
                        step="0.01"
                        value={openRegisterForm.opening_balance}
                        onChange={e => setOpenRegisterForm(p => ({ ...p, opening_balance: parseFloat(e.target.value) || 0 }))}
                      />
                    </div>
                  </div>
                  <Button onClick={openRegisterHandle} disabled={openRegisterMutation.isPending}>
                    <Plus className="w-4 h-4 mr-2" /> Abrir Caixa
                  </Button>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="fechamento">
          <Card className="mt-4">
            <CardHeader><CardTitle>Fechamento de Caixa</CardTitle></CardHeader>
            <CardContent>
              {currentOpenRegister ? (
                <div className="space-y-4">
                  <div className="grid grid-cols-3 gap-4">
                    <Card>
                      <CardHeader className="pb-1"><CardTitle className="text-sm text-muted-foreground">Saldo Abertura</CardTitle></CardHeader>
                       <CardContent><p className="text-2xl font-mono">{currentOpenRegister.opening_balance?.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p></CardContent>
                    </Card>
                    <Card>
                      <CardHeader className="pb-1"><CardTitle className="text-sm text-muted-foreground">Total Creditos</CardTitle></CardHeader>
                       <CardContent><p className="text-2xl font-mono text-green-600">{currentOpenRegister.total_credits?.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p></CardContent>
                    </Card>
                    <Card>
                      <CardHeader className="pb-1"><CardTitle className="text-sm text-muted-foreground">Total Debitos</CardTitle></CardHeader>
                       <CardContent><p className="text-2xl font-mono text-red-600">{currentOpenRegister.total_debits?.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p></CardContent>
                    </Card>
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="text-sm text-muted-foreground">Saldo Fechamento (calculado)</label>
                      <Input
                        type="number"
                        step="0.01"
                         value={currentOpenRegister.closing_balance || (currentOpenRegister.opening_balance + currentOpenRegister.total_credits - currentOpenRegister.total_debits)}
                        readOnly
                      />
                    </div>
                    <div>
                      <label className="text-sm text-muted-foreground">Observacoes</label>
                      <Textarea placeholder="Observacoes do fechamento" rows={2} />
                    </div>
                  </div>
                   <Button onClick={() => closeRegisterHandle(currentOpenRegister)}>
                    <Check className="w-4 h-4 mr-2" /> Fechar Caixa
                  </Button>
                </div>
              ) : (
                <div className="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                  <p className="text-yellow-800">Nenhum caixa aberto para fechar</p>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="lancamento">
          <Card className="mt-4">
            <CardHeader><CardTitle>Lancamento de Movimento</CardTitle></CardHeader>
            <CardContent>
              {currentOpenRegister ? (
                <div className="space-y-4 max-w-2xl">
                  <div className="grid grid-cols-2 gap-4">
                    <Select value={movementForm.type} onValueChange={v => setMovementForm(p => ({ ...p, type: v as 'credit' | 'debit' }))}>
                      <SelectTrigger><SelectValue placeholder="Tipo" /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="credit">Credito (Entrada)</SelectItem>
                        <SelectItem value="debit">Debito (Saida)</SelectItem>
                      </SelectContent>
                    </Select>
                    <Input
                      type="number"
                      step="0.01"
                      placeholder="Valor"
                      value={movementForm.amount}
                      onChange={e => setMovementForm(p => ({ ...p, amount: parseFloat(e.target.value) || 0 }))}
                    />
                  </div>
                  <Input
                    placeholder="Descricao"
                    value={movementForm.description}
                    onChange={e => setMovementForm(p => ({ ...p, description: e.target.value }))}
                  />
                  <div className="grid grid-cols-2 gap-4">
                    <Input
                      placeholder="Categoria"
                      value={movementForm.category}
                      onChange={e => setMovementForm(p => ({ ...p, category: e.target.value }))}
                    />
                    <Select value={movementForm.payment_method} onValueChange={v => setMovementForm(p => ({ ...p, payment_method: v }))}>
                      <SelectTrigger><SelectValue placeholder="Forma Pagamento" /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="dinheiro">Dinheiro</SelectItem>
                        <SelectItem value="pix">PIX</SelectItem>
                        <SelectItem value="cartao">Cartao</SelectItem>
                        <SelectItem value="cheque">Cheque</SelectItem>
                        <SelectItem value="transferencia">Transferencia</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <Input
                    placeholder="Documento (opcional)"
                    value={movementForm.document}
                    onChange={e => setMovementForm(p => ({ ...p, document: e.target.value }))}
                  />
                  <Button onClick={() => addMovementHandle(currentOpenRegister)} disabled={!movementForm.description || movementForm.amount <= 0}>
                    <Plus className="w-4 h-4 mr-2" /> Adicionar Movimento
                  </Button>
                </div>
              ) : (
                <div className="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                  <p className="text-yellow-800">Abra o caixa primeiro para lancar movimentos</p>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="extrato">
          <Card className="mt-4">
            <CardHeader className="pb-2">
              <CardTitle>Extrato de Caixa</CardTitle>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mt-2">
                <Input type="date" value={periodo.data_inicio} onChange={e => setPeriodo(p => ({ ...p, data_inicio: e.target.value }))} placeholder="Data Inicio" />
                <Input type="date" value={periodo.data_fim} onChange={e => setPeriodo(p => ({ ...p, data_fim: e.target.value }))} placeholder="Data Fim" />
              </div>
            </CardHeader>
            <CardContent>
              {registers?.data?.map((register: CashRegister) => (
                <div key={register.id} className="mb-6">
                  <div className="flex items-center justify-between mb-2">
                    <h4 className="font-medium">{format(new Date(register.date), 'dd/MM/yyyy')} - {register.operator}</h4>
                    <Badge variant={register.status === 'open' ? 'warning' : 'success'}>{register.status === 'open' ? 'Aberto' : 'Fechado'}</Badge>
                  </div>
                  {register.movements?.map((mov: CashMovement, idx: number) => (
                    <div key={mov.id || idx} className="flex items-center justify-between py-1 border-b">
                      <span className="text-sm">{mov.description}</span>
                      <Badge variant={mov.type === 'credit' ? 'success' : 'destructive'} className="mr-2">
                        {mov.type === 'credit' ? '+' : '-'}{mov.amount?.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                      </Badge>
                      <span className="text-sm text-muted-foreground">{mov.category || '-'}</span>
                    </div>
                  ))}
                  <div className="flex justify-end text-sm text-muted-foreground mt-2">
                    Creditos: {register.total_credits?.toLocaleString('pt-BR', { minimumFractionDigits: 2 })} |
                    Debitos: {register.total_debits?.toLocaleString('pt-BR', { minimumFractionDigits: 2 })} |
                    Saldo: {register.closing_balance?.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
