import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
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
  Shield,
  Key,
  Ban,
  Trash2,
  Clock,
  CheckCircle,
  XCircle,
  AlertTriangle,
  Loader2,
  Plus,
} from 'lucide-react';

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:9080/api';

interface License {
  id: number;
  key: string;
  type: 'trial' | 'community' | 'enterprise';
  status: 'active' | 'suspended' | 'revoked' | 'expired';
  customer_name: string | null;
  customer_email: string | null;
  activated_at: string | null;
  expires_at: string | null;
  last_validated_at: string | null;
  validation_count: number;
  activation_count: number;
  max_activations: number;
  features: string[] | null;
  created_at: string;
}

export default function LicensesPage() {
  const queryClient = useQueryClient();
  const [activateDialog, setActivateDialog] = useState(false);
  const [trialDialog, setTrialDialog] = useState(false);
  const [selectedLicense, setSelectedLicense] = useState<License | null>(null);
  const [actionDialog, setActionDialog] = useState<'suspend' | 'revoke' | null>(null);
  const [licenseKey, setLicenseKey] = useState('');
  const [trialOrgId, setTrialOrgId] = useState('');
  const [trialDays, setTrialDays] = useState('14');
  const [reason, setReason] = useState('');

  const { data: licenses, isLoading } = useQuery({
    queryKey: ['licenses'],
    queryFn: async () => {
      const { data } = await axios.get(`${API_BASE}/license`);
      return data.licenses as License[];
    },
  });

  const { data: licenseInfo } = useQuery({
    queryKey: ['license-info'],
    queryFn: async () => {
      const { data } = await axios.get(`${API_BASE}/license/info`);
      return data;
    },
  });

  const activateMutation = useMutation({
    mutationFn: async (key: string) => {
      const { data } = await axios.post(`${API_BASE}/license/activate`, { key });
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['licenses'] });
      queryClient.invalidateQueries({ queryKey: ['license-info'] });
      setActivateDialog(false);
      setLicenseKey('');
    },
  });

  const trialMutation = useMutation({
    mutationFn: async ({ orgId, days }: { orgId: string; days: string }) => {
      const { data } = await axios.post(`${API_BASE}/license/trial`, {
        organization_id: parseInt(orgId),
        days: parseInt(days),
      });
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['licenses'] });
      setTrialDialog(false);
      setTrialOrgId('');
      setTrialDays('14');
    },
  });

  const suspendMutation = useMutation({
    mutationFn: async ({ id, reason }: { id: number; reason: string }) => {
      const { data } = await axios.post(`${API_BASE}/license/${id}/suspend`, { reason });
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['licenses'] });
      setActionDialog(null);
      setSelectedLicense(null);
      setReason('');
    },
  });

  const revokeMutation = useMutation({
    mutationFn: async ({ id, reason }: { id: number; reason: string }) => {
      const { data } = await axios.post(`${API_BASE}/license/${id}/revoke`, { reason });
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['licenses'] });
      queryClient.invalidateQueries({ queryKey: ['license-info'] });
      setActionDialog(null);
      setSelectedLicense(null);
      setReason('');
    },
  });

  const getTypeBadge = (type: string) => {
    const variants: Record<string, 'default' | 'secondary' | 'outline'> = {
      enterprise: 'default',
      community: 'secondary',
      trial: 'outline',
    };
    return <Badge variant={variants[type] || 'secondary'}>{type}</Badge>;
  };

  const getStatusBadge = (status: string) => {
    const colors: Record<string, string> = {
      active: 'bg-green-100 text-green-800',
      suspended: 'bg-yellow-100 text-yellow-800',
      revoked: 'bg-red-100 text-red-800',
      expired: 'bg-gray-100 text-gray-800',
    };
    return (
      <span className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${colors[status] || ''}`}>
        {status}
      </span>
    );
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Licenças</h1>
          <p className="text-muted-foreground">Gerencie licenças do SCHF</p>
        </div>
        <div className="flex gap-2">
          <Dialog open={trialDialog} onOpenChange={setTrialDialog}>
            <DialogTrigger asChild>
              <Button variant="outline">
                <Clock className="mr-2 h-4 w-4" />
                Criar Trial
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Criar Licença Trial</DialogTitle>
                <DialogDescription>
                  Crie uma licença de avaliação para uma organização.
                </DialogDescription>
              </DialogHeader>
              <div className="space-y-4 py-4">
                <div className="space-y-2">
                  <label className="text-sm font-medium">ID da Organização</label>
                  <input
                    type="number"
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    placeholder="1"
                    value={trialOrgId}
                    onChange={(e) => setTrialOrgId(e.target.value)}
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium">Dias</label>
                  <input
                    type="number"
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    placeholder="14"
                    value={trialDays}
                    onChange={(e) => setTrialDays(e.target.value)}
                    min="1"
                    max="90"
                  />
                </div>
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setTrialDialog(false)}>Cancelar</Button>
                <Button
                  onClick={() => trialMutation.mutate({ orgId: trialOrgId, days: trialDays })}
                  disabled={!trialOrgId || trialMutation.isPending}
                >
                  {trialMutation.isPending ? 'Criando...' : 'Criar Trial'}
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>

          <Dialog open={activateDialog} onOpenChange={setActivateDialog}>
            <DialogTrigger asChild>
              <Button>
                <Key className="mr-2 h-4 w-4" />
                Ativar Licença
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Ativar Licença</DialogTitle>
                <DialogDescription>
                  Insira a chave de licença para ativar.
                </DialogDescription>
              </DialogHeader>
              <div className="space-y-4 py-4">
                <div className="space-y-2">
                  <label className="text-sm font-medium">Chave de Licença</label>
                  <input
                    type="text"
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm font-mono"
                    placeholder="SCHF-XXXX-XXXX-XXXX-XXXX"
                    value={licenseKey}
                    onChange={(e) => setLicenseKey(e.target.value)}
                  />
                </div>
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setActivateDialog(false)}>Cancelar</Button>
                <Button
                  onClick={() => activateMutation.mutate(licenseKey)}
                  disabled={!licenseKey || activateMutation.isPending}
                >
                  {activateMutation.isPending ? 'Ativando...' : 'Ativar'}
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      {/* License Status Card */}
      {licenseInfo && (
        <div className="rounded-lg border bg-card p-6">
          <div className="flex items-center gap-4">
            <div className={`p-3 rounded-full ${licenseInfo.licensed ? 'bg-green-100' : 'bg-gray-100'}`}>
              <Shield className={`h-6 w-6 ${licenseInfo.licensed ? 'text-green-600' : 'text-gray-400'}`} />
            </div>
            <div>
              <h3 className="font-semibold">
                {licenseInfo.licensed ? 'SCHF Licenciado' : 'SCHF Não Licenciado'}
              </h3>
              <p className="text-sm text-muted-foreground">
                {licenseInfo.licensed
                  ? `Tipo: ${licenseInfo.type} | Status: ${licenseInfo.status}`
                  : 'Ative uma licença para usar todas as funcionalidades'}
              </p>
            </div>
          </div>
        </div>
      )}

      {/* Licenses Table */}
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Chave</TableHead>
              <TableHead>Tipo</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Cliente</TableHead>
              <TableHead>Expira em</TableHead>
              <TableHead>Ativações</TableHead>
              <TableHead className="text-right">Ações</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              <TableRow>
                <TableCell colSpan={7} className="text-center py-8">
                  <Loader2 className="h-6 w-6 animate-spin mx-auto text-muted-foreground" />
                </TableCell>
              </TableRow>
            ) : !licenses?.length ? (
              <TableRow>
                <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                  Nenhuma licença encontrada
                </TableCell>
              </TableRow>
            ) : (
              licenses.map((license) => (
                <TableRow key={license.id}>
                  <TableCell className="font-mono text-sm">{license.key}</TableCell>
                  <TableCell>{getTypeBadge(license.type)}</TableCell>
                  <TableCell>{getStatusBadge(license.status)}</TableCell>
                  <TableCell>{license.customer_name || '—'}</TableCell>
                  <TableCell>
                    {license.expires_at
                      ? new Date(license.expires_at).toLocaleDateString('pt-BR')
                      : 'Permanente'}
                  </TableCell>
                  <TableCell>{license.activation_count}/{license.max_activations}</TableCell>
                  <TableCell className="text-right">
                    <div className="flex justify-end gap-1">
                      {license.status === 'active' && (
                        <>
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => { setSelectedLicense(license); setActionDialog('suspend'); setReason(''); }}
                            title="Suspender"
                          >
                            <Ban className="h-4 w-4 text-yellow-500" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => { setSelectedLicense(license); setActionDialog('revoke'); setReason(''); }}
                            title="Revogar"
                          >
                            <Trash2 className="h-4 w-4 text-destructive" />
                          </Button>
                        </>
                      )}
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      {/* Suspend/Revoke Dialog */}
      <Dialog open={!!actionDialog} onOpenChange={() => { setActionDialog(null); setSelectedLicense(null); }}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              {actionDialog === 'suspend' ? (
                <><AlertTriangle className="h-5 w-5 text-yellow-500" /> Suspender Licença</>
              ) : (
                <><XCircle className="h-5 w-5 text-destructive" /> Revogar Licença</>
              )}
            </DialogTitle>
            <DialogDescription>
              {actionDialog === 'suspend'
                ? 'A licença será temporariamente desativada.'
                : 'A licença será permanentemente revogada. Esta ação não pode ser desfeita.'}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="rounded-md bg-muted p-3 font-mono text-sm">
              {selectedLicense?.key}
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">Motivo (opcional)</label>
              <input
                type="text"
                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                placeholder="Motivo da ação"
                value={reason}
                onChange={(e) => setReason(e.target.value)}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => { setActionDialog(null); setSelectedLicense(null); }}>
              Cancelar
            </Button>
            <Button
              variant={actionDialog === 'revoke' ? 'destructive' : 'default'}
              onClick={() => {
                if (!selectedLicense) return;
                if (actionDialog === 'suspend') {
                  suspendMutation.mutate({ id: selectedLicense.id, reason });
                } else {
                  revokeMutation.mutate({ id: selectedLicense.id, reason });
                }
              }}
              disabled={suspendMutation.isPending || revokeMutation.isPending}
            >
              {(suspendMutation.isPending || revokeMutation.isPending) ? 'Processando...' :
                actionDialog === 'suspend' ? 'Suspender' : 'Revogar'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
