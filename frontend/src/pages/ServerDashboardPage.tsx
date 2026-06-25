import { useState, useEffect, useCallback } from 'react'
import { useConfigStore } from '../stores/configStore'
import { tauriApi } from '../services/tauriCommands'
import { healthCheck } from '../services/api'
import { Button } from '../components/ui/Button'
import { Badge } from '../components/ui/Badge'
import { Card, CardHeader, CardTitle, CardContent } from '../components/ui/Card'
import type { ContainerInfo, NetworkInfo } from '../types/tauri'

export function ServerDashboardPage() {
  const config = useConfigStore()
  const [containers, setContainers] = useState<ContainerInfo[]>([])
  const [netInfo, setNetInfo] = useState<NetworkInfo | null>(null)
  const [dockerRunning, setDockerRunning] = useState(false)
  const [actionMsg, setActionMsg] = useState('')
  const [backendOnline, setBackendOnline] = useState(false)
  const [loading, setLoading] = useState(false)

  const refresh = useCallback(async () => {
    setLoading(true)
    setActionMsg('')
    try {
      const dr = await tauriApi.checkDockerRunning()
      setDockerRunning(dr)

      if (dr) {
        const appDir = await tauriApi.getAppDataDir()
        const dd = config.dockerDir || `${appDir}/docker`
        const c = await tauriApi.getContainers(dd)
        setContainers(c)
      }

      const ni = await tauriApi.getNetworkInfo()
      setNetInfo(ni)

      const ok = await healthCheck()
      setBackendOnline(ok)
    } catch {
      // ignore
    }
    setLoading(false)
  }, [config.dockerDir])

  useEffect(() => {
    refresh()
    const interval = setInterval(refresh, 15000)
    return () => clearInterval(interval)
  }, [refresh])

  const handleStartContainers = async () => {
    setActionMsg('Iniciando containers...')
    try {
      const dir = config.dockerDir || ''
      await tauriApi.dockerComposeUp(dir)
      setActionMsg('Containers iniciados!')
      refresh()
    } catch (e: unknown) {
      setActionMsg(e instanceof Error ? e.message : String(e))
    }
  }

  const handleStopContainers = async () => {
    setActionMsg('Parando containers...')
    try {
      const dir = config.dockerDir || ''
      await tauriApi.dockerComposeDown(dir)
      setActionMsg('Containers parados.')
      refresh()
    } catch (e: unknown) {
      setActionMsg(e instanceof Error ? e.message : String(e))
    }
  }

  const handleStartDiscovery = async () => {
    try {
      await tauriApi.startDiscoveryServer()
      setActionMsg('Serviço de descoberta ativo!')
    } catch (e: unknown) {
      setActionMsg(e instanceof Error ? e.message : String(e))
    }
  }

  return (
    <div className="space-y-6 p-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Painel do Servidor</h1>
        <div className="flex items-center gap-2">
          <Badge variant={dockerRunning ? 'success' : 'destructive'}>
            Docker: {dockerRunning ? 'Online' : 'Offline'}
          </Badge>
          <Badge variant={backendOnline ? 'success' : 'destructive'}>
            API: {backendOnline ? 'Online' : 'Offline'}
          </Badge>
          <Button variant="outline" size="sm" onClick={refresh} disabled={loading}>
            {loading ? 'Atualizando...' : 'Atualizar'}
          </Button>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Containers</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {containers.length === 0 ? (
              <p className="text-sm text-muted-foreground">Nenhum container encontrado.</p>
            ) : (
              containers.map((c) => (
                <div key={c.name} className="flex items-center justify-between p-2 rounded-md border text-sm">
                  <span className="font-mono text-xs truncate">{c.name}</span>
                  <Badge variant={c.status.includes('Up') ? 'success' : 'warning'}>
                    {c.status}
                  </Badge>
                </div>
              ))
            )}

            <div className="flex gap-2 pt-2">
              <Button onClick={handleStartContainers} size="sm" disabled={!dockerRunning}>
                Iniciar
              </Button>
              <Button onClick={handleStopContainers} variant="outline" size="sm" disabled={!dockerRunning}>
                Parar
              </Button>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Rede</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2 text-sm">
            {netInfo ? (
              <>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Hostname:</span>
                  <span className="font-mono">{netInfo.hostname}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">IP:</span>
                  <span className="font-mono">{netInfo.ips[0] || '---'}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Gateway:</span>
                  <span className="font-mono">{netInfo.gateway || '---'}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">IP Fixo:</span>
                  <Badge variant={netInfo.is_static ? 'success' : 'warning'}>
                    {netInfo.is_static ? 'Sim' : 'Não'}
                  </Badge>
                </div>
              </>
            ) : (
              <p className="text-muted-foreground">Detectando...</p>
            )}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Descoberta LAN</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <p className="text-sm text-muted-foreground">
            Ative o serviço de descoberta para que clientes na rede encontrem este servidor automaticamente.
          </p>
          <Button onClick={handleStartDiscovery} variant="secondary">
            Ativar Descoberta
          </Button>

          {config.apiUrl && (
            <div className="rounded-md bg-muted p-3 text-sm">
              <p className="font-medium">URL para clientes:</p>
              <p className="font-mono text-xs mt-1 select-all">{config.apiUrl}</p>
            </div>
          )}
        </CardContent>
      </Card>

      {actionMsg && (
        <div className="rounded-md bg-primary/10 border border-primary/20 p-3 text-sm text-center">
          {actionMsg}
        </div>
      )}
    </div>
  )
}
