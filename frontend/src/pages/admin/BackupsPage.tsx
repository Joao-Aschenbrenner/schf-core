import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { backupService, Backup } from '../../services/backups';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '../../components/ui/Dialog';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '../../components/ui/Table';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '../../components/ui/Select';
import {
  Shield,
  Download,
  Trash2,
  RotateCcw,
  Database,
  FileArchive,
  HardDrive,
  Loader2,
  AlertTriangle,
} from 'lucide-react';

export default function BackupsPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [typeFilter, setTypeFilter] = useState<string>('');
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [createDialogOpen, setCreateDialogOpen] = useState(false);
  const [restoreDialog, setRestoreDialog] = useState<Backup | null>(null);
  const [deleteDialog, setDeleteDialog] = useState<Backup | null>(null);
  const [password, setPassword] = useState('');
  const [backupType, setBackupType] = useState<'full' | 'database' | 'files'>('full');

  const { data, isLoading } = useQuery({
    queryKey: ['admin-backups', page, typeFilter, statusFilter],
    queryFn: () => backupService.list(page, 15, typeFilter || undefined, statusFilter || undefined),
  });

  const createMutation = useMutation({
    mutationFn: () => backupService.create(backupType, password),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-backups'] });
      setCreateDialogOpen(false);
      setPassword('');
    },
  });

  const restoreMutation = useMutation({
    mutationFn: (id: number) => backupService.restore(id, password),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-backups'] });
      setRestoreDialog(null);
      setPassword('');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => backupService.destroy(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-backups'] });
      setDeleteDialog(null);
    },
  });

  const cleanupMutation = useMutation({
    mutationFn: () => backupService.cleanup(),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-backups'] }),
  });

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'full': return <HardDrive className="h-4 w-4" />;
      case 'database': return <Database className="h-4 w-4" />;
      case 'files': return <FileArchive className="h-4 w-4" />;
      default: return <Database className="h-4 w-4" />;
    }
  };

  const getStatusBadge = (status: string) => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
      completed: 'default',
      pending: 'secondary',
      running: 'outline',
      failed: 'destructive',
      restoring: 'destructive',
    };
    return <Badge variant={variants[status] || 'secondary'}>{status}</Badge>;
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Backups</h1>
          <p className="text-muted-foreground">Gerencie backups do sistema</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => cleanupMutation.mutate()} disabled={cleanupMutation.isPending}>
            Limpar Antigos
          </Button>
          <Dialog open={createDialogOpen} onOpenChange={setCreateDialogOpen}>
            <DialogTrigger asChild>
              <Button>
                <Shield className="mr-2 h-4 w-4" />
                Novo Backup
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Criar Backup</DialogTitle>
                <DialogDescription>
                  Crie um backup manual do sistema. Você precisará inserir sua senha para confirmar.
                </DialogDescription>
              </DialogHeader>
              <div className="space-y-4 py-4">
                <div className="space-y-2">
                  <label className="text-sm font-medium">Tipo de Backup</label>
                  <Select value={backupType} onValueChange={(v) => setBackupType(v as typeof backupType)}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="full">Completo (Banco + Arquivos)</SelectItem>
                      <SelectItem value="database">Apenas Banco de Dados</SelectItem>
                      <SelectItem value="files">Apenas Arquivos</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium">Senha de Confirmação</label>
                  <input
                    type="password"
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    placeholder="Sua senha de login"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                  />
                </div>
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setCreateDialogOpen(false)}>Cancelar</Button>
                <Button
                  onClick={() => createMutation.mutate()}
                  disabled={!password || createMutation.isPending}
                >
                  {createMutation.isPending ? 'Criando...' : 'Criar Backup'}
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <div className="flex gap-2">
        <Select value={typeFilter} onValueChange={(v) => { setTypeFilter(v === 'all' ? '' : v); setPage(1); }}>
          <SelectTrigger className="w-48">
            <SelectValue placeholder="Todos os tipos" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Todos os tipos</SelectItem>
            <SelectItem value="full">Completo</SelectItem>
            <SelectItem value="database">Banco de Dados</SelectItem>
            <SelectItem value="files">Arquivos</SelectItem>
          </SelectContent>
        </Select>
        <Select value={statusFilter} onValueChange={(v) => { setStatusFilter(v === 'all' ? '' : v); setPage(1); }}>
          <SelectTrigger className="w-48">
            <SelectValue placeholder="Todos os status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Todos os status</SelectItem>
            <SelectItem value="completed">Concluído</SelectItem>
            <SelectItem value="pending">Pendente</SelectItem>
            <SelectItem value="running">Em execução</SelectItem>
            <SelectItem value="failed">Falhou</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nome</TableHead>
              <TableHead>Tipo</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Tamanho</TableHead>
              <TableHead>Criado em</TableHead>
              <TableHead className="text-right">Ações</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              <TableRow>
                <TableCell colSpan={6} className="text-center py-8">
                  <Loader2 className="h-6 w-6 animate-spin mx-auto text-muted-foreground" />
                </TableCell>
              </TableRow>
            ) : data?.data.length === 0 ? (
              <TableRow>
                <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                  Nenhum backup encontrado
                </TableCell>
              </TableRow>
            ) : (
              data?.data.map((backup) => (
                <TableRow key={backup.id}>
                  <TableCell className="font-medium">{backup.name}</TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      {getTypeIcon(backup.type)}
                      <span className="capitalize">{backup.type}</span>
                    </div>
                  </TableCell>
                  <TableCell>{getStatusBadge(backup.status)}</TableCell>
                  <TableCell>{backupService.formatSize(backup.file_size)}</TableCell>
                  <TableCell>
                    {new Date(backup.created_at).toLocaleDateString('pt-BR', {
                      day: '2-digit',
                      month: '2-digit',
                      year: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit',
                    })}
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="flex justify-end gap-1">
                      {backup.status === 'completed' && (
                        <>
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => backupService.download(backup.id)}
                            title="Download"
                          >
                            <Download className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => { setRestoreDialog(backup); setPassword(''); }}
                            title="Restaurar"
                          >
                            <RotateCcw className="h-4 w-4" />
                          </Button>
                        </>
                      )}
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => setDeleteDialog(backup)}
                        title="Excluir"
                      >
                        <Trash2 className="h-4 w-4 text-destructive" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      {data && data.last_page > 1 && (
        <div className="flex justify-center gap-2">
          <Button variant="outline" disabled={page === 1} onClick={() => setPage(page - 1)}>
            Anterior
          </Button>
          <span className="py-2 px-4 text-sm text-muted-foreground">
            Página {data.current_page} de {data.last_page}
          </span>
          <Button variant="outline" disabled={page === data.last_page} onClick={() => setPage(page + 1)}>
            Próxima
          </Button>
        </div>
      )}

      {/* Restore Dialog */}
      <Dialog open={!!restoreDialog} onOpenChange={() => setRestoreDialog(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <AlertTriangle className="h-5 w-5 text-destructive" />
              Restaurar Backup
            </DialogTitle>
            <DialogDescription>
              Esta ação irá restaurar o sistema para o ponto do backup <strong>{restoreDialog?.name}</strong>.
              Um backup de segurança será criado antes do restore.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="rounded-md bg-destructive/10 p-4 text-sm text-destructive">
              <strong>Atenção:</strong> O sistema ficará indisponível durante o restore.
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">Senha de Confirmação</label>
              <input
                type="password"
                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                placeholder="Sua senha de login"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setRestoreDialog(null)}>Cancelar</Button>
            <Button
              variant="destructive"
              onClick={() => restoreDialog && restoreMutation.mutate(restoreDialog.id)}
              disabled={!password || restoreMutation.isPending}
            >
              {restoreMutation.isPending ? 'Restaurando...' : 'Restaurar'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Delete Dialog */}
      <Dialog open={!!deleteDialog} onOpenChange={() => setDeleteDialog(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Excluir Backup</DialogTitle>
            <DialogDescription>
              Tem certeza que deseja excluir o backup <strong>{deleteDialog?.name}</strong>?
              Esta ação não pode ser desfeita.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteDialog(null)}>Cancelar</Button>
            <Button
              variant="destructive"
              onClick={() => deleteDialog && deleteMutation.mutate(deleteDialog.id)}
              disabled={deleteMutation.isPending}
            >
              {deleteMutation.isPending ? 'Excluindo...' : 'Excluir'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
