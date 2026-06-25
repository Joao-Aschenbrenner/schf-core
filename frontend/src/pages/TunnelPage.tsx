import { useState, useEffect, useCallback } from 'react'
import { useConfigStore } from '../stores/configStore'
import { tauriApi } from '../services/tauriCommands'
import { Button } from '../components/ui/Button'
import { Badge } from '../components/ui/Badge'
import { Input } from '../components/ui/Input'
import { Label } from '../components/ui/Label'
import { Card, CardHeader, CardTitle, CardContent } from '../components/ui/Card'

export function TunnelPage() {
  const config = useConfigStore()
  const [cloudflaredFound, setCloudflaredFound] = useState(false)
  const [ngrokFound, setNgrokFound] = useState(false)
  const [tunnelType, setTunnelType] = useState<'cloudflare' | 'ngrok'>('cloudflare')
  const [token, setToken] = useState('')
  const [localUrl, setLocalUrl] = useState(config.apiUrl)
  const [status, setStatus] = useState<TunnelInfo>({ running: false, url: '', tunnel_type: '' })
  const [msg, setMsg] = useState('')
  const [err, setErr] = useState('')
  const [loading, setLoading] = useState(false)

  interface TunnelInfo {
    running: boolean
    url: string
    tunnel_type: string
  }

  const checkTools = useCallback(async () => {
    const [cf, ng] = await Promise.all([
      tauriApi.checkCloudflared().catch(() => false),
      tauriApi.checkNgrok().catch(() => false),
    ])
    setCloudflaredFound(cf)
    setNgrokFound(ng)
  }, [])

  const refreshStatus = useCallback(async () => {
    try {
      const info = await tauriApi.getTunnelInfo()
      setStatus(info)
    } catch {
      setStatus({ running: false, url: '', tunnel_type: '' })
    }
  }, [])

  useEffect(() => {
    checkTools()
    refreshStatus()
    const interval = setInterval(refreshStatus, 5000)
    return () => clearInterval(interval)
  }, [checkTools, refreshStatus])

  const handleStart = async () => {
    setLoading(true)
    setMsg('')
    setErr('')
    try {
      const baseUrl = localUrl.replace(/\/api\/?$/, '').replace(/\/+$/, '')
      const result = await tauriApi.startTunnel(tunnelType, baseUrl, token)
      setMsg(result)
      setTimeout(refreshStatus, 3000)
    } catch (e: unknown) {
      setErr(e instanceof Error ? e.message : String(e))
    }
    setLoading(false)
  }

  const handleStop = async () => {
    setLoading(true)
    try {
      await tauriApi.stopTunnel()
      setMsg('Tunnel parado.')
      refreshStatus()
    } catch (e: unknown) {
      setErr(e instanceof Error ? e.message : String(e))
    }
    setLoading(false)
  }

  return (
    <div className="space-y-6 p-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Túnel de Acesso Externo</h1>
        <Badge variant={status.running ? 'success' : 'secondary'}>
          {status.running ? 'Ativo' : 'Inativo'}
        </Badge>
      </div>

      <p className="text-muted-foreground text-sm">
        Configure um túnel para acessar o sistema de fora da rede local (ex: de casa, pelo celular).
        Funciona com Cloudflare Tunnel (recomendado) ou Ngrok.
      </p>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Ferramentas Detectadas</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3 text-sm">
            <div className="flex items-center justify-between p-2 rounded-md border">
              <span>Cloudflare Tunnel (cloudflared)</span>
              <Badge variant={cloudflaredFound ? 'success' : 'destructive'}>
                {cloudflaredFound ? 'Instalado' : 'Não encontrado'}
              </Badge>
            </div>
            <div className="flex items-center justify-between p-2 rounded-md border">
              <span>Ngrok</span>
              <Badge variant={ngrokFound ? 'success' : 'destructive'}>
                {ngrokFound ? 'Instalado' : 'Não encontrado'}
              </Badge>
            </div>

            {!cloudflaredFound && !ngrokFound && (
              <div className="rounded-md bg-amber-50 border border-amber-200 p-3 text-xs">
                <p className="font-medium text-amber-800">Nenhum tunnel detectado</p>
                <p className="text-amber-700 mt-1">
                  Instale o cloudflared: <span className="font-mono select-all">winget install cloudflare.cloudflared</span>
                </p>
                <p className="text-amber-700">
                  Ou ngrok: <span className="font-mono select-all">winget install ngrok</span>
                </p>
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Status do Túnel</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3 text-sm">
            <div className="flex items-center justify-between p-2 rounded-md border">
              <span>Status</span>
              <Badge variant={status.running ? 'success' : 'secondary'}>
                {status.running ? 'Conectado' : 'Desconectado'}
              </Badge>
            </div>
            <div className="flex items-center justify-between p-2 rounded-md border">
              <span>Tipo</span>
              <span className="font-mono text-xs">{status.tunnel_type || '---'}</span>
            </div>
            <div>
              <span className="text-xs text-muted-foreground block mb-1">URL Pública</span>
              {status.url ? (
                <div className="flex gap-2">
                  <Input
                    value={status.url}
                    readOnly
                    className="font-mono text-xs"
                    onClick={(e) => (e.target as HTMLInputElement).select()}
                  />
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => { navigator.clipboard.writeText(status.url); setMsg('URL copiada!') }}
                  >
                    Copiar
                  </Button>
                </div>
              ) : (
                <p className="text-muted-foreground text-xs">Inicie o túnel para obter a URL</p>
              )}
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Configurar Túnel</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex gap-4">
            <Button
              onClick={() => setTunnelType('cloudflare')}
              variant={tunnelType === 'cloudflare' ? 'default' : 'outline'}
              className="flex-1"
              disabled={!cloudflaredFound}
            >
              ☁️ Cloudflare Tunnel
            </Button>
            <Button
              onClick={() => setTunnelType('ngrok')}
              variant={tunnelType === 'ngrok' ? 'default' : 'outline'}
              className="flex-1"
              disabled={!ngrokFound}
            >
              🔌 Ngrok
            </Button>
          </div>

          <div className="space-y-2">
            <Label htmlFor="local-url">URL Local (base)</Label>
            <Input
              id="local-url"
              value={localUrl}
              onChange={(e) => setLocalUrl(e.target.value)}
              placeholder="http://localhost:9080"
            />
            <p className="text-xs text-muted-foreground">
              URL base do backend (sem /api). Padrão: <span className="font-mono">http://localhost:9080</span>
            </p>
          </div>

          <div className="space-y-2">
            <Label htmlFor="token">
              {tunnelType === 'cloudflare' ? 'Token do Tunnel (opcional)' : 'Token do Ngrok (opcional)'}
            </Label>
            <Input
              id="token"
              type="password"
              value={token}
              onChange={(e) => setToken(e.target.value)}
              placeholder={tunnelType === 'cloudflare' ? 'Deixe vazio para tunnel rápido (trycloudflare.com)' : 'ngrok authtoken'}
            />
            <p className="text-xs text-muted-foreground">
              {tunnelType === 'cloudflare'
                ? 'Com token: tunnel gerenciado (domínio próprio). Sem token: URL aleatória trycloudflare.com.'
                : 'Token do ngrok.com. Sem token: limite de 1 túnel por 2h (gratuito).'}
            </p>
          </div>

          {err && (
            <div className="rounded-md bg-destructive/10 border border-destructive/20 p-3 text-sm text-destructive">
              {err}
            </div>
          )}
          {msg && (
            <div className="rounded-md bg-primary/10 border border-primary/20 p-3 text-sm">{msg}</div>
          )}

          <div className="flex gap-2">
            <Button onClick={handleStart} disabled={loading || status.running} className="flex-1">
              {loading ? 'Iniciando...' : 'Iniciar Túnel'}
            </Button>
            <Button onClick={handleStop} disabled={loading || !status.running} variant="destructive">
              Parar
            </Button>
          </div>
        </CardContent>
      </Card>

      {tunnelType === 'cloudflare' && !token && (
        <Card>
          <CardHeader>
            <CardTitle>Cloudflare Tunnel Rápido</CardTitle>
          </CardHeader>
          <CardContent className="text-sm text-muted-foreground space-y-2">
            <p>
              O <strong>tunnel rápido</strong> cria uma URL aleatória <code>*.trycloudflare.com</code>
              sem necessidade de conta. Perfeito para testes.
            </p>
            <p>
              Para um <strong>tunnel gerenciado</strong> (domínio próprio, mais estável), use o
              argumento <code>--token</code> do cloudflared. Você precisa de uma conta Cloudflare
              com um domínio configurado.
            </p>
            <p className="text-xs text-muted-foreground border-t pt-2 mt-2">
              <strong>Importante:</strong> O túnel expõe o backend à internet. Use com senha forte
              e considere HTTPS. O Laravel já está configurado com HTTPS trust proxies.
            </p>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
