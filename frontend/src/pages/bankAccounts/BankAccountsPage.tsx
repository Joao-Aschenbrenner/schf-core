import { useQuery } from '@tanstack/react-query'
import { bankAccountService } from '../../services/bankAccounts'
import { Button } from '../../components/ui/Button'
import { Card, CardContent } from '../../components/ui/Card'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../../components/ui/Table'
import { Badge } from '../../components/ui/Badge'
import { formatCurrency } from '../../utils/format'

export function BankAccountsPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['bank-accounts'],
    queryFn: () => bankAccountService.list(),
  })

  const typeLabels: Record<string, string> = {
    checking: 'Corrente',
    savings: 'Poupança',
    investment: 'Aplicação',
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Contas Bancárias</h2>
          <p className="text-muted-foreground">Contas correntes, poupança e aplicações</p>
        </div>
        <Button>Nova Conta</Button>
      </div>

      <Card>
        <CardContent className="pt-6">
          {isLoading ? (
            <div className="text-center py-8 text-muted-foreground">Carregando...</div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Banco</TableHead>
                  <TableHead>Agência</TableHead>
                  <TableHead>Conta</TableHead>
                  <TableHead>Tipo</TableHead>
                  <TableHead>Saldo</TableHead>
                  <TableHead>Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data?.data?.map((account: any) => (
                  <TableRow key={account.id}>
                    <TableCell className="font-medium">{account.bank_name}</TableCell>
                    <TableCell>{account.agency}</TableCell>
                    <TableCell>{account.account}</TableCell>
                    <TableCell><Badge variant="outline">{typeLabels[account.type] || account.type}</Badge></TableCell>
                    <TableCell>{formatCurrency(account.current_balance)}</TableCell>
                    <TableCell>
                      <Badge variant={account.is_active ? 'success' : 'secondary'}>
                        {account.is_active ? 'Ativa' : 'Inativa'}
                      </Badge>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
