import { useQuery } from '@tanstack/react-query'
import { expenseCategoryService } from '../../services/expenseCategories'
import { Button } from '../../components/ui/Button'
import { Card, CardContent } from '../../components/ui/Card'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '../../components/ui/Table'
import { Badge } from '../../components/ui/Badge'

export function ExpenseCategoriesPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['expense-categories'],
    queryFn: () => expenseCategoryService.list(),
  })

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold tracking-tight">Categorias de Despesa</h2>
          <p className="text-muted-foreground">Classificação oficial de gastos</p>
        </div>
        <Button>Nova Categoria</Button>
      </div>

      <Card>
        <CardContent className="pt-6">
          {isLoading ? (
            <div className="text-center py-8 text-muted-foreground">Carregando...</div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Código</TableHead>
                  <TableHead>Nome</TableHead>
                  <TableHead>Pai</TableHead>
                  <TableHead>Permitido</TableHead>
                  <TableHead>Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data?.map((cat: any) => (
                  <TableRow key={cat.id}>
                    <TableCell className="font-mono">{cat.code}</TableCell>
                    <TableCell className="font-medium">{cat.name}</TableCell>
                    <TableCell>{cat.parent?.name || '-'}</TableCell>
                    <TableCell>
                      <Badge variant={cat.is_allowed_by_default ? 'success' : 'warning'}>
                        {cat.is_allowed_by_default ? 'Sim' : 'Não'}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <Badge variant={cat.is_active ? 'success' : 'secondary'}>
                        {cat.is_active ? 'Ativa' : 'Inativa'}
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
