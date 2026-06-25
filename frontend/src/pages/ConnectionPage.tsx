import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import { useConfigStore } from '../stores/configStore'
import { healthCheck } from '../services/api'
import { tauriApi } from '../services/tauriCommands'
import { Button } from '../components/ui/Button'
import { Input } from '../components/ui/Input'
import { Label } from '../components/ui/Label'
import { Card, CardHeader, CardTitle, CardContent } from '../components/ui/Card'
import { Badge } from '../components/ui/Badge'
import type { DiscoveryResult } from '../types/tauri'

const presetUrls = [
  { label: 'Local (localhost)', url: 'http://localhost:9080/api' },
  { label: 'Rede interna (192.168.0.10)', url: 'http://192.168.0.10:9080/api' },
  { label: 'VPS (producao)', url: 'https://financeiro.hospital.local/api' },
]

export function ConnectionPage() {
  const navigate = useNavigate()
  const {
    apiUrl,
    backendStatus,
    lastHealthCheck,
    serverMode,
    setupCompleted,
    setApiUrl,
    setEnvironment,
    setServerMode,
    setSetupCompleted,
  } = useConfigStore()
  const [customUrl, setCustomUrl] = useState(apiUrl)
  const [checking, setChecking] = useState(false)
  const [discovering, setDiscovering] = useState(false)
  const [servers, setServers] = useState<DiscoveryResult[]>([])
  const [isTauri, setIsTauri] = useState(false)
  const [showDiscovery, setShowDiscovery] = useState(false)

  useEffect(() => {
    setIsTauri(typeof window !== 'undefined' && '__TAURI_INTERNALS__' in window)
  }, [])

  const check = useCallback(async () => {
    setChecking(true)
    await healthCheck()
    setChecking(false)
  }, [])

  const runSetup = () => {
    navigate('/setup')
  }

  const handleDiscover = async () => {
    if (!isTauri) return
    setDiscovering(true)
    setServers([])
    setShowDiscovery(true)
    try {
      const results = await tauriApi.discoverServers()
      setServers(results)
    } catch {
      // ignore
    }
    setDiscovering(false)
  }

  const connectToServer = (server: DiscoveryResult) => {
    const url = `http://${server.ip}:${server.port}/api`
    setCustomUrl(url)
    setApiUrl(url)
    setEnvironment('network')
    setServerMode('client')
    check()
  }

  const handleSave = () => {
    setApiUrl(customUrl)
    setEnvironment(
      customUrl.includes('localhost') ? 'local'
        : customUrl.includes('192.168') ? 'network'
        : 'vps'
    )
    check()
  }

  const statusVariant = backendStatus === 'online' ? 'success'
    : backendStatus === 'checking' ? 'warning'
    : 'destructive'

  const statusLabel = backendStatus === 'online' ? 'Online'
    : backendStatus === 'checking' ? 'Verificando...'
    : backendStatus === 'offline' ? 'Offline'
    : 'Desconhecido'

  return (
    <div className="min-h-screen flex items-center justify-center bg-muted p-4">
      <div className="w-full max-w-lg space-y-6">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-primary">SCHF</h1>
          <p className="text-muted-foreground mt-1">ConfiguraÃ§Ã£o de ConexÃ£o</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center justify-between">
              Modo de OperaÃ§Ã£o
              <Badge>
                {serverMode === 'server' ? 'Servidor'
                  : serverMode === 'client' ? 'Cliente'
                  : 'NÃ£o configurado'}
              </Badge>
            </CardTitle>
          </CardHeader>
          <CardContent>
            {!setupCompleted && isTauri && (
              <div className="rounded-md bg-primary/10 border border-primary/20 p-3 mb-3">
                <p className="text-sm font-medium">Primeira vez?</p>
                <p className="text-xs text-muted-foreground mt-1">
                  Use o assistente de configuraÃ§Ã£o para configurar o sistema automaticamente.
                </p>
                <Button onClick={runSetup} size="sm" className="mt-2">
                  Abrir Assistente
                </Button>
              </div>
            )}

            <div className="flex gap-2">
              <Button
                onClick={() => { setServerMode('server'); setSetupCompleted(true); navigate('/') }}
                variant={serverMode === 'server' ? 'default' : 'outline'}
                size="sm"
                className="flex-1"
                disabled={!isTauri}
              >
                ðŸ–¥ï¸ Servidor
              </Button>
              <Button
                onClick={() => { setServerMode('client'); navigate('/conexao') }}
                variant={serverMode === 'client' ? 'default' : 'outline'}
                size="sm"
                className="flex-1"
              >
                ðŸ’» Cliente
              </Button>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center justify-between">
              Status do Backend
              <Badge variant={statusVariant}>{statusLabel}</Badge>
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {lastHealthCheck && (
              <p className="text-xs text-muted-foreground">
                Ãšltima verificaÃ§Ã£o: {new Date(lastHealthCheck).toLocaleString('pt-BR')}
              </p>
            )}

            {backendStatus === 'offline' && (
              <div className="rounded-md bg-destructive/10 border border-destructive/20 p-3">
                <p className="text-sm text-destructive font-medium">Backend nÃ£o disponÃ­vel</p>
                <p className="text-xs text-muted-foreground mt-1">
                  Verifique se o servidor estÃ¡ rodando e a URL estÃ¡ correta.
                </p>
              </div>
            )}

            {backendStatus === 'online' && (
              <div className="rounded-md bg-green-50 border border-green-200 p-3">
                <p className="text-sm text-green-800 font-medium">Backend conectado com sucesso</p>
              </div>
            )}

            <Button onClick={check} disabled={checking} variant="outline" className="w-full">
              {checking ? 'Verificando...' : 'Verificar ConexÃ£o'}
            </Button>
          </CardContent>
        </Card>

        {isTauri && (
          <Card>
            <CardHeader>
              <CardTitle>Descoberta AutomÃ¡tica</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <p className="text-sm text-muted-foreground">
                Procura por servidores SCHF na rede local.
              </p>

              <Button onClick={handleDiscover} disabled={discovering} variant="secondary" className="w-full">
                {discovering ? 'Procurando...' : 'Procurar Servidores na Rede'}
              </Button>

              {showDiscovery && servers.length === 0 && !discovering && (
                <p className="text-xs text-muted-foreground text-center">
                  Nenhum servidor encontrado. Certifique-se de que o servidor estÃ¡ com a descoberta ativada.
                </p>
              )}

              {servers.length > 0 && (
                <div className="space-y-2">
                  <p className="text-xs font-medium text-muted-foreground">Servidores encontrados:</p>
                  {servers.map((s) => (
                    <button
                      key={s.ip}
                      onClick={() => connectToServer(s)}
                      className="w-full text-left rounded-md border p-3 text-sm hover:bg-accent transition-colors"
                    >
                      <span className="font-medium">{s.hostname}</span>
                      <span className="text-muted-foreground ml-2 font-mono text-xs">
                        {s.ip}:{s.port}
                      </span>
                    </button>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        )}

        <Card>
          <CardHeader>
            <CardTitle>URL da API</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="api-url">EndereÃ§o do Backend</Label>
              <Input
                id="api-url"
                value={customUrl}
                onChange={(e) => setCustomUrl(e.target.value)}
                placeholder="http://localhost:9080/api"
              />
            </div>

            <div className="space-y-2">
              <Label>Presets</Label>
              <div className="grid gap-2">
                {presetUrls.map((preset) => (
                  <button
                    key={preset.url}
                    onClick={() => setCustomUrl(preset.url)}
                    className={`text-left rounded-md border px-3 py-2 text-sm transition-colors ${
                      customUrl === preset.url
                        ? 'bg-primary text-primary-foreground border-primary'
                        : 'hover:bg-muted'
                    }`}
                  >
                    {preset.label}
                  </button>
                ))}
              </div>
            </div>

            <Button onClick={handleSave} className="w-full">
              Salvar e Testar
            </Button>
            {backendStatus === 'online' && (
              <Button onClick={() => navigate('/login')} className="w-full mt-2">
                Continuar
              </Button>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}


