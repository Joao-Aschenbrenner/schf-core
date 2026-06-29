import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { historicoService } from '@/services/historicoApi'
import { bankOperationService } from '@/services/bankOperationApi'
import type { ExtratoBancario } from '@/types'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/Select'
import { Card, CardHeader, CardContent, CardTitle } from '@/components/ui/Card'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/Table'
import { Download } from 'lucide-react'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/Tabs'

export function ExtratoBancarioPage() {
  const [mode, setMode] = useState<'historico' | 'operacional'>('historico')
  const [contaId, setContaId] = useState<number | null>(null)
  const [periodo, setPeriodo] = useState({ data_inicio: '', data_fim: '' })
  const [extrato, setExtrato] = useState<ExtratoBancario | null>(null)
  const [loading, setLoading] = useState(false)

  const { data: contasHistorico } = useQuery({
    queryKey: ['historico-contas-all'],
    queryFn: () => historicoService.listContas({ per_page: 1000 }),
  })

  const { data: contasOperacional } = useQuery({
    queryKey: ['bank-accounts-all'],
    queryFn: () => bankOperationService.list?.({ per_page: 1000 }),
  })

  const contas = mode === 'historico' ? contasHistorico?.data || [] : contasOperacional?.data || []

  const handleConsultar = async () => {
    if (!contaId || !periodo.data_inicio || !periodo.data_fim) {
      alert('Selecione conta e periodo')
      return
    }
    setLoading(true)
    try {
      if (mode === 'historico') {
        const data = await historicoService.getExtratoBancario({
          conta_id: contaId,
          data_inicio: periodo.data_inicio,
          data_fim: periodo.data_fim,
        })
        setExtrato(data)
      } else {
        const data = await bankOperationService.extrato({
          bank_account_id: contaId,
          data_inicio: periodo.data_inicio,
          data_fim: periodo.data_fim,
        })
        setExtrato(data)
      }
    } catch (error) {
      console.error('Erro ao buscar extrato:', error)
      alert('Erro ao buscar extrato')
    } finally {
      setLoading(false)
    }
  }

  const handleExport = (type: 'csv' | 'xlsx') => {
    if (!extrato) return
    console.log(`Export ${type} for extrato`)
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Extrato Bancario</h2>
          <p className="text-muted-foreground">Consulta de extratos bancarios historicos e operacionais</p>
        </div>
        <div className="flex items-center gap-2">
          <Button variant="outline" size="sm" onClick={() => handleExport('csv')}>
            <Download className="w-4 h-4 mr-2" /> CSV
          </Button>
          <Button variant="outline" size="sm" onClick={() => handleExport('xlsx')}>
            <Download className="w-4 h-4 mr-2" /> XLSX
          </Button>
        </div>
      </div>

      <Tabs value={mode} onValueChange={value => setMode(value as typeof mode)} className="w-full mb-4">
        <TabsList className="grid w-full grid-cols-2">
          <TabsTrigger value="historico">Historico (SGH)</TabsTrigger>
          <TabsTrigger value="operacional">Operacional (2026+)</TabsTrigger>
        </TabsList>
      </Tabs>

      <Card>
        <CardHeader className="pb-2">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
            <Select value={contaId?.toString() || ''} onValueChange={v => setContaId(v ? parseInt(v) : null)}>
              <SelectTrigger><SelectValue placeholder="Selecionar Conta" /></SelectTrigger>
              <SelectContent>
                {contas.map((c: any) => (
                  <SelectItem key={c.id} value={c.id.toString()}>
                    {c.banco || c.bank_name} - {c.agencia || c.agency}/{c.conta_corrente || c.account}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Input type="date" value={periodo.data_inicio} onChange={e => setPeriodo(p => ({ ...p, data_inicio: e.target.value }))} placeholder="Data Inicio" />
            <Input type="date" value={periodo.data_fim} onChange={e => setPeriodo(p => ({ ...p, data_fim: e.target.value }))} placeholder="Data Fim" />
          </div>
          <Button onClick={handleConsultar} disabled={loading} className="mt-2">
            {loading ? 'Consultando...' : 'Consultar Extrato'}
          </Button>
        </CardHeader>
        <CardContent>
          {extrato && (
            <div className="space-y-4">
              <div className="grid grid-cols-3 gap-4">
                <Card>
                  <CardHeader className="pb-1"><CardTitle className="text-sm text-muted-foreground">Saldo Inicial</CardTitle></CardHeader>
                  <CardContent><p className="text-2xl font-mono">{extrato.saldo_inicial?.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p></CardContent>
                </Card>
                <Card>
                  <CardHeader className="pb-1"><CardTitle className="text-sm text-muted-foreground">Total Creditos</CardTitle></CardHeader>
                  <CardContent><p className="text-2xl font-mono text-green-600">{extrato.total_creditos?.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) || '0,00'}</p></CardContent>
                </Card>
                <Card>
                  <CardHeader className="pb-1"><CardTitle className="text-sm text-muted-foreground">Total Debitos</CardTitle></CardHeader>
                  <CardContent><p className="text-2xl font-mono text-red-600">{extrato.total_debitos?.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) || '0,00'}</p></CardContent>
                </Card>
              </div>

              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Data</TableHead>
                    <TableHead>Descricao</TableHead>
                    <TableHead className="text-right">Credito</TableHead>
                    <TableHead className="text-right">Debito</TableHead>
                    <TableHead className="text-right">Saldo</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {extrato.operacoes?.map((op: any, idx: number) => {
                    const credito = op.credito_debito === 'C' || op.type === 'credit' ? op.valor : 0
                    const debito = op.credito_debito === 'D' || op.type === 'debit' ? op.valor : 0
                    return (
                      <TableRow key={op.id || idx}>
                        <TableCell>{op.data_operacao || op.data || op.date}</TableCell>
                        <TableCell>{op.descricao || op.historico || op.description || '-'}</TableCell>
                        <TableCell className="text-right font-mono text-green-600">{credito ? credito.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) : '-'}</TableCell>
                        <TableCell className="text-right font-mono text-red-600">{debito ? debito.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) : '-'}</TableCell>
                        <TableCell className="text-right font-mono">{op.saldo_acumulado?.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) || op.saldo_final?.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) || '-'}</TableCell>
                      </TableRow>
                    )
                  })}
                </TableBody>
              </Table>

              <Card>
                <CardHeader className="pb-1"><CardTitle className="text-sm text-muted-foreground">Saldo Final</CardTitle></CardHeader>
                <CardContent><p className="text-2xl font-mono font-bold">{extrato.saldo_final?.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p></CardContent>
              </Card>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
