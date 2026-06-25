import { useState } from 'react'
import { useStatements, useStatement, useImportOfx, useAutoMatch, useConciliate, useUnmatch } from '../../hooks/useConciliation'
import { usePayables } from '../../hooks/usePayables'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Card, CardHeader, CardContent } from '../../components/ui/Card'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../../components/ui/Table'
import { Badge } from '../../components/ui/Badge'

const statusMap: Record<string, { label: string; variant: string }> = {
  imported: { label: 'Importado', variant: 'secondary' },
  reconciled: { label: 'Reconciliado', variant: 'success' },
  closed: { label: 'Fechado', variant: 'default' },
}

export function ConciliationPage() {
  const [page, setPage] = useState(1)
  const [selectedStatement, setSelectedStatement] = useState<number | null>(null)
  const [showImportModal, setShowImportModal] = useState(false)
  const [showConciliateModal, setShowConciliateModal] = useState(false)
  const [conciliatingItemId, setConciliatingItemId] = useState<number | null>(null)

  const { data: statements, isLoading } = useStatements({ page, per_page: 15 })
  const importMutation = useImportOfx()
  const autoMatchMutation = useAutoMatch()
  const conciliateMutation = useConciliate()
  const unmatchMutation = useUnmatch()

  const handleImportOfx = async (bankAccountId: number, file: File) => {
    const content = await file.text()
    await importMutation.mutateAsync({ bankAccountId, ofxContent: content })
    setShowImportModal(false)
  }

  const handleAutoMatch = async (statementId: number) => {
    const result = await autoMatchMutation.mutateAsync(statementId)
    alert(`${result.matched_count} item(s) conciliado(s) automaticamente`)
  }

  const handleConciliate = async (payableId: number) => {
    if (conciliatingItemId) {
      await conciliateMutation.mutateAsync({ statementItemId: conciliatingItemId, payableId })
      setShowConciliateModal(false)
      setConciliatingItemId(null)
    }
  }

  const handleUnmatch = async (itemId: number) => {
    if (confirm('Confirma desfazer a conciliação deste item?')) {
      await unmatchMutation.mutateAsync(itemId)
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Conciliação Bancária</h2>
          <p className="text-muted-foreground">Importação OFX e conciliação com pagamentos</p>
        </div>
        <Button onClick={() => setShowImportModal(true)}>Importar OFX</Button>
      </div>

      <Card>
        <CardHeader>
          <h3 className="text-lg font-semibold">Extratos Bancários</h3>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8 text-muted-foreground">Carregando...</div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Conta</TableHead>
                  <TableHead>Data</TableHead>
                  <TableHead>Saldo Inicial</TableHead>
                  <TableHead>Saldo Final</TableHead>
                  <TableHead>Itens</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Ações</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {statements?.data?.map((statement: any) => (
                  <TableRow key={statement.id}>
                    <TableCell>{statement.bank_account?.bank_name || '-'}</TableCell>
                    <TableCell>{new Date(statement.statement_date).toLocaleDateString('pt-BR')}</TableCell>
                    <TableCell>R$ {Number(statement.opening_balance).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</TableCell>
                    <TableCell>R$ {Number(statement.closing_balance).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</TableCell>
                    <TableCell>{statement.items?.length || 0}</TableCell>
                    <TableCell>
                      <Badge variant={statusMap[statement.status]?.variant as any || 'secondary'}>
                        {statusMap[statement.status]?.label || statement.status}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right space-x-2">
                      <Button variant="outline" size="sm" onClick={() => setSelectedStatement(statement.id)}>
                        Detalhes
                      </Button>
                      {statement.status === 'imported' && (
                        <Button variant="default" size="sm" onClick={() => handleAutoMatch(statement.id)}>
                          Conciliar Auto
                        </Button>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}

          {statements?.meta && (
            <div className="flex items-center justify-between mt-4">
              <p className="text-sm text-muted-foreground">
                {statements.meta.total} extrato(s) encontrado(s)
              </p>
              <div className="space-x-2">
                <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage(page - 1)}>
                  Anterior
                </Button>
                <Button variant="outline" size="sm"                   disabled={page >= statements.meta.last_page} onClick={() => setPage(page + 1)}>
                  Próximo
                </Button>
              </div>
            </div>
          )}
        </CardContent>
      </Card>

      {selectedStatement && (
        <StatementDetail
          statementId={selectedStatement}
          onClose={() => setSelectedStatement(null)}
          onConciliate={(itemId) => { setConciliatingItemId(itemId); setShowConciliateModal(true) }}
          onUnmatch={handleUnmatch}
        />
      )}

      {showImportModal && (
        <ImportOfxModal
          onImport={handleImportOfx}
          onClose={() => setShowImportModal(false)}
          isLoading={importMutation.isPending}
        />
      )}

      {showConciliateModal && conciliatingItemId && (
        <ConciliateModal
          onSelect={handleConciliate}
          onClose={() => { setShowConciliateModal(false); setConciliatingItemId(null) }}
          isLoading={conciliateMutation.isPending}
        />
      )}
    </div>
  )
}

function StatementDetail({ statementId, onClose, onConciliate, onUnmatch }: {
  statementId: number
  onClose: () => void
  onConciliate: (itemId: number) => void
  onUnmatch: (itemId: number) => void
}) {
  const { data: statement, isLoading } = useStatement(statementId)

  if (isLoading) return <div className="text-center py-8">Carregando...</div>

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <h3 className="text-lg font-semibold">Detalhes do Extrato</h3>
        <Button variant="outline" size="sm" onClick={onClose}>Fechar</Button>
      </CardHeader>
      <CardContent>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Data</TableHead>
              <TableHead>Descrição</TableHead>
              <TableHead>Documento</TableHead>
              <TableHead>Tipo</TableHead>
              <TableHead>Valor</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="text-right">Ações</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {statement?.items?.map((item: any) => (
              <TableRow key={item.id}>
                <TableCell>{new Date(item.transaction_date).toLocaleDateString('pt-BR')}</TableCell>
                <TableCell>{item.description}</TableCell>
                <TableCell>{item.document_id || '-'}</TableCell>
                <TableCell>
                  <Badge variant={item.type === 'credit' ? 'success' : 'destructive'}>
                    {item.type === 'credit' ? 'Crédito' : 'Débito'}
                  </Badge>
                </TableCell>
                <TableCell>R$ {Number(item.amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</TableCell>
                <TableCell>
                  <Badge variant={item.is_reconciled ? 'success' : 'secondary'}>
                    {item.is_reconciled ? 'Conciliado' : 'Pendente'}
                  </Badge>
                </TableCell>
                <TableCell className="text-right">
                  {!item.is_reconciled && item.type === 'debit' && (
                    <Button variant="outline" size="sm" onClick={() => onConciliate(item.id)}>
                      Conciliar
                    </Button>
                  )}
                  {item.is_reconciled && (
                    <Button variant="destructive" size="sm" onClick={() => onUnmatch(item.id)}>
                      Desfazer
                    </Button>
                  )}
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  )
}

function ImportOfxModal({ onImport, onClose, isLoading }: {
  onImport: (bankAccountId: number, file: File) => void
  onClose: () => void
  isLoading: boolean
}) {
  const [bankAccountId, setBankAccountId] = useState('')
  const [file, setFile] = useState<File | null>(null)

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (bankAccountId && file) {
      onImport(Number(bankAccountId), file)
    }
  }

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <Card className="w-full max-w-md">
        <CardHeader>
          <h3 className="text-lg font-semibold">Importar Extrato OFX</h3>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium mb-1">Conta Bancária *</label>
              <select
                value={bankAccountId}
                onChange={(e) => setBankAccountId(e.target.value)}
                className="w-full border rounded-md px-3 py-2 text-sm"
                required
              >
                <option value="">Selecione...</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Arquivo OFX *</label>
              <Input
                type="file"
                accept=".ofx,.OFX"
                onChange={(e) => setFile(e.target.files?.[0] || null)}
                required
              />
            </div>
            <div className="flex justify-end gap-4">
              <Button type="button" variant="outline" onClick={onClose}>Cancelar</Button>
              <Button type="submit" disabled={isLoading}>
                {isLoading ? 'Importando...' : 'Importar'}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}

function ConciliateModal({ onSelect, onClose, isLoading }: {
  onSelect: (payableId: number) => void
  onClose: () => void
  isLoading: boolean
}) {
  const [payableId, setPayableId] = useState('')
  const { data: payablesData } = usePayables({ status: 'pending', per_page: 100 })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (payableId) {
      onSelect(Number(payableId))
    }
  }

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <Card className="w-full max-w-md">
        <CardHeader>
          <h3 className="text-lg font-semibold">Conciliar com Pagamento</h3>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium mb-1">Pagamento *</label>
              <select
                value={payableId}
                onChange={(e) => setPayableId(e.target.value)}
                className="w-full border rounded-md px-3 py-2 text-sm"
                required
              >
                <option value="">Selecione...</option>
                {payablesData?.data?.map((p: any) => (
                  <option key={p.id} value={p.id}>
                    {p.description} — R$ {Number(p.amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                  </option>
                ))}
              </select>
            </div>
            <div className="flex justify-end gap-4">
              <Button type="button" variant="outline" onClick={onClose}>Cancelar</Button>
              <Button type="submit" disabled={isLoading}>
                {isLoading ? 'Conciliando...' : 'Conciliar'}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}

export default ConciliationPage
