import { useState } from 'react'
import { useAuditTrail } from '../../hooks/useAuditTrail'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Card, CardHeader, CardContent } from '../../components/ui/Card'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../../components/ui/Table'
import { Badge } from '../../components/ui/Badge'

const actionLabels: Record<string, string> = {
  payable_created: 'Pagamento Criado',
  payable_updated: 'Pagamento Atualizado',
  payable_approved: 'Pagamento Aprovado',
  payable_paid: 'Pagamento Realizado',
  payable_cancelled: 'Pagamento Cancelado',
  payable_generated_from_nfe: 'Gerado de NF-e',
  nfe_created: 'NF-e Criada',
  nfe_updated: 'NF-e Atualizada',
  nfe_confirmed: 'NF-e Confirmada',
  nfe_cancelled: 'NF-e Cancelada',
  supplier_created: 'Fornecedor Criado',
  supplier_updated: 'Fornecedor Atualizado',
  bank_account_created: 'Conta Bancária Criada',
  bank_statement_imported: 'Extrato Importado',
  statement_item_conciliated: 'Item Conciliado',
  statement_item_unmatched: 'Conciliação Desfeita',
  bank_statement_fully_conciliated: 'Extrato Conciliado',
  dda_imported: 'DDA Importado',
  dda_linked_to_payable: 'DDA Vinculado a Pagamento',
  dda_rejected: 'DDA Rejeitado',
}

export function AuditTrailPage() {
  const [page, setPage] = useState(1)
  const [actionFilter, setActionFilter] = useState('')
  const [dateFrom, setDateFrom] = useState('')
  const [dateTo, setDateTo] = useState('')

  const { data, isLoading } = useAuditTrail({
    action: actionFilter || undefined,
    date_from: dateFrom || undefined,
    date_to: dateTo || undefined,
    page,
    per_page: 20,
  })

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Trilha de Auditoria</h2>
          <p className="text-muted-foreground">Histórico de todas as ações do sistema</p>
        </div>
      </div>

      <Card>
        <CardHeader>
          <div className="flex gap-4">
            <select
              value={actionFilter}
              onChange={(e) => setActionFilter(e.target.value)}
              className="border rounded-md px-3 py-2 text-sm"
            >
              <option value="">Todas as ações</option>
              {Object.entries(actionLabels).map(([key, label]) => (
                <option key={key} value={key}>{label}</option>
              ))}
            </select>
            <Input
              type="date"
              value={dateFrom}
              onChange={(e) => setDateFrom(e.target.value)}
              className="max-w-[160px]"
            />
            <Input
              type="date"
              value={dateTo}
              onChange={(e) => setDateTo(e.target.value)}
              className="max-w-[160px]"
            />
          </div>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8 text-muted-foreground">Carregando...</div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Data/Hora</TableHead>
                  <TableHead>Usuário</TableHead>
                  <TableHead>Ação</TableHead>
                  <TableHead>Modelo</TableHead>
                  <TableHead>ID</TableHead>
                  <TableHead>Detalhes</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data?.data?.map((entry: any) => (
                  <TableRow key={entry.id}>
                    <TableCell className="text-sm">
                      {new Date(entry.created_at).toLocaleString('pt-BR')}
                    </TableCell>
                    <TableCell>{entry.user?.name || '-'}</TableCell>
                    <TableCell>
                      <Badge variant="outline">
                        {actionLabels[entry.action] || entry.action}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-sm text-muted-foreground">
                      {entry.model_type?.split('\\').pop() || '-'}
                    </TableCell>
                    <TableCell>{entry.model_id}</TableCell>
                    <TableCell className="text-sm text-muted-foreground max-w-[200px] truncate">
                      {entry.properties ? JSON.stringify(entry.properties).substring(0, 100) + '...' : '-'}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}

          {data?.meta && (
            <div className="flex items-center justify-between mt-4">
              <p className="text-sm text-muted-foreground">
                {data.meta.total} registro(s) encontrado(s)
              </p>
              <div className="space-x-2">
                <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage(page - 1)}>
                  Anterior
                </Button>
                <Button variant="outline" size="sm" disabled={page >= data.meta.last_page} onClick={() => setPage(page + 1)}>
                  Próximo
                </Button>
              </div>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}

export default AuditTrailPage
