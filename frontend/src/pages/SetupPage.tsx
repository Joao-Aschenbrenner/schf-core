import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { useSetupStore } from '../stores/setupStore'
import { useConfigStore } from '../stores/configStore'
import { tauriApi } from '../services/tauriCommands'
import { healthCheck } from '../services/api'
import { Button } from '../components/ui/Button'
import { Badge } from '../components/ui/Badge'
import { Card, CardContent } from '../components/ui/Card'
import type { NetworkInfo } from '../types/tauri'

function WelcomeStep({ onNext }: { onNext: () => void }) {
  return (
    <div className="text-center space-y-6 py-8">
      <div className="text-6xl">ðŸ¥</div>
      <h2 className="text-2xl font-bold">SCHF</h2>
      <p className="text-muted-foreground max-w-md mx-auto">
        Bem-vindo ao assistente de configuraÃ§Ã£o. Vamos preparar o sistema para funcionar na sua rede.
      </p>
      <div className="space-y-3 text-left max-w-sm mx-auto text-sm text-muted-foreground">
        <p className="flex items-start gap-2">
          <span className="text-primary mt-0.5">1.</span>
          <span>Escolher modo de operaÃ§Ã£o (Servidor ou Cliente)</span>
        </p>
        <p className="flex items-start gap-2">
          <span className="text-primary mt-0.5">2.</span>
          <span>Configurar Docker e containers</span>
        </p>
        <p className="flex items-start gap-2">
          <span className="text-primary mt-0.5">3.</span>
          <span>Configurar rede (IP fixo)</span>
        </p>
        <p className="flex items-start gap-2">
          <span className="text-primary mt-0.5">4.</span>
          <span>Testar conexÃ£o e comeÃ§ar a usar</span>
        </p>
      </div>
      <Button onClick={onNext} size="lg">ComeÃ§ar ConfiguraÃ§Ã£o</Button>
    </div>
  )
}

function ModeStep({ onNext, onBack }: { onNext: (mode: 'server' | 'client') => void; onBack: () => void }) {
  const { checkDockerInstalled } = tauriApi
  const [dockerFound, setDockerFound] = useState<boolean | null>(null)

  useEffect(() => {
    checkDockerInstalled().then(setDockerFound).catch(() => setDockerFound(false))
  }, [checkDockerInstalled])

  return (
    <div className="space-y-6 py-8">
      <h2 className="text-xl font-bold text-center">Modo de OperaÃ§Ã£o</h2>
      <p className="text-sm text-muted-foreground text-center">
        Como este computador serÃ¡ usado?
      </p>

      {dockerFound === false && (
        <div className="rounded-md bg-amber-50 border border-amber-200 p-3 text-sm text-amber-800">
          Docker nÃ£o foi detectado nesta mÃ¡quina. O modo Cliente serÃ¡ recomendado.
        </div>
      )}

      <div className="grid grid-cols-2 gap-4 max-w-lg mx-auto">
        <button
          onClick={() => dockerFound !== false && onNext('server')}
          disabled={dockerFound === false}
          className={`rounded-xl border-2 p-6 text-left transition-all hover:border-primary ${
            dockerFound === false ? 'opacity-40 cursor-not-allowed' : 'hover:bg-accent'
          }`}
        >
          <div className="text-3xl mb-2">ðŸ–¥ï¸</div>
          <h3 className="font-semibold">Servidor</h3>
          <p className="text-xs text-muted-foreground mt-1">
            Hospeda o backend e banco de dados via Docker. Outros computadores da rede se conectam a este.
          </p>
        </button>
        <button
          onClick={() => onNext('client')}
          className="rounded-xl border-2 p-6 text-left transition-all hover:border-primary hover:bg-accent"
        >
          <div className="text-3xl mb-2">ðŸ’»</div>
          <h3 className="font-semibold">Cliente</h3>
          <p className="text-xs text-muted-foreground mt-1">
            Apenas se conecta ao servidor existente na rede. NÃ£o precisa de Docker.
          </p>
        </button>
      </div>

      <div className="flex justify-between max-w-lg mx-auto">
        <Button variant="outline" onClick={onBack}>Voltar</Button>
      </div>
    </div>
  )
}

function DockerCheckStep({
  onNext,
  onBack,
}: {
  onNext: () => void
  onBack: () => void
}) {
  const setup = useSetupStore()
  const config = useConfigStore()
  const [loading, setLoading] = useState(false)
  const [actionMsg, setActionMsg] = useState('')

  useEffect(() => {
    const check = async () => {
      setLoading(true)
      const installed = await tauriApi.checkDockerInstalled()
      setup.setDockerInstalled(installed)
      if (installed) {
        const running = await tauriApi.checkDockerRunning()
        setup.setDockerRunning(running)
      }
      setLoading(false)
    }
    check()
  }, [])

  const handleSetupContainers = async () => {
    setLoading(true)
    setActionMsg('Iniciando containers...')

    try {
      const dataDir = await tauriApi.getAppDataDir()
      const dockerDir = dataDir ? `${dataDir}/docker` : ''
      config.setDockerDir(dockerDir)

      if (!setup.dockerRunning) {
        setActionMsg('Aguardando Docker iniciar... Por favor, inicie o Docker Desktop manualmente.')
        setup.setError('Docker Desktop precisa estar rodando. Abra o Docker Desktop e clique em "Verificar Novamente".')
        setLoading(false)
        return
      }

      const containers = await tauriApi.getContainers(dockerDir)
      setup.setHasContainers(containers.length > 0)

      if (containers.length === 0) {
        setActionMsg('Criando containers...')
        const result = await tauriApi.dockerComposeUp(dockerDir)
        if (result) {
          setup.setContainersStarted(true)
          setActionMsg('Containers iniciados!')
        }
      } else {
        const allRunning = containers.every((c) => c.status.includes('Up'))
        if (!allRunning) {
          await tauriApi.dockerComposeUp(dockerDir)
        }
        setup.setContainersStarted(true)
        setup.setHasContainers(true)
        setActionMsg('Containers jÃ¡ estÃ£o rodando!')
      }
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : String(err)
      setup.setError(msg)
    }
    setLoading(false)
  }

  return (
    <div className="space-y-6 py-8 max-w-lg mx-auto">
      <h2 className="text-xl font-bold text-center">ConfiguraÃ§Ã£o do Docker</h2>

      <div className="space-y-3">
        <div className="flex items-center justify-between p-3 rounded-lg border">
          <span className="text-sm">Docker Instalado</span>
          <Badge variant={setup.dockerInstalled ? 'success' : 'destructive'}>
            {setup.dockerInstalled ? 'Sim' : 'NÃ£o'}
          </Badge>
        </div>
        <div className="flex items-center justify-between p-3 rounded-lg border">
          <span className="text-sm">Docker em ExecuÃ§Ã£o</span>
          <Badge variant={setup.dockerRunning ? 'success' : 'warning'}>
            {setup.dockerRunning ? 'Rodando' : 'Parado'}
          </Badge>
        </div>
        <div className="flex items-center justify-between p-3 rounded-lg border">
          <span className="text-sm">Containers</span>
          <Badge variant={setup.hasContainers ? (setup.containersStarted ? 'success' : 'warning') : 'secondary'}>
            {setup.containersStarted ? 'Rodando' : setup.hasContainers ? 'Parados' : 'NÃ£o criados'}
          </Badge>
        </div>
      </div>

      {actionMsg && <p className="text-sm text-muted-foreground text-center">{actionMsg}</p>}

      {setup.error && (
        <div className="rounded-md bg-destructive/10 border border-destructive/20 p-3 text-sm text-destructive">
          {setup.error}
        </div>
      )}

      <div className="flex justify-between">
        <Button variant="outline" onClick={onBack} disabled={loading}>Voltar</Button>
        <div className="space-x-2">
          <Button variant="secondary" onClick={handleSetupContainers} disabled={loading || !setup.dockerInstalled}>
            {loading ? 'Aguarde...' : 'Configurar Containers'}
          </Button>
          <Button onClick={onNext} disabled={!setup.containersStarted || loading}>
            Continuar
          </Button>
        </div>
      </div>
    </div>
  )
}

function NetworkStep({
  onNext,
  onBack,
}: {
  onNext: () => void
  onBack: () => void
}) {
  const [netInfo, setNetInfo] = useState<NetworkInfo | null>(null)
  const [loading, setLoading] = useState(false)
  const [configuring, setConfiguring] = useState(false)
  const [msg, setMsg] = useState('')
  const [err, setErr] = useState('')
  const config = useConfigStore()

  useEffect(() => {
    setLoading(true)
    tauriApi.getNetworkInfo().then(setNetInfo).catch(() => {}).finally(() => setLoading(false))
  }, [])

  const handleSetStatic = async () => {
    if (!netInfo) return
    setConfiguring(true)
    setErr('')
    try {
      const ip = netInfo.ips[0] || ''
      if (!ip) throw new Error('Nenhum IP detectado')
      const parts = ip.split('.')
      const gateway = `${parts[0]}.${parts[1]}.${parts[2]}.1`
      const mask = '255.255.255.0'
      const result = await tauriApi.setStaticIp(ip, mask, gateway, netInfo.adapter_name)
      setMsg(result)
    } catch (e: unknown) {
      setErr(e instanceof Error ? e.message : String(e))
    }
    setConfiguring(false)
  }

  const handleSetDhcp = async () => {
    if (!netInfo) return
    setConfiguring(true)
    setErr('')
    try {
      const result = await tauriApi.setDynamicIp(netInfo.adapter_name)
      setMsg(result)
    } catch (e: unknown) {
      setErr(e instanceof Error ? e.message : String(e))
    }
    setConfiguring(false)
  }

  const suggestedUrl = netInfo?.ips[0] ? `http://${netInfo.ips[0]}:9080/api` : ''

  return (
    <div className="space-y-6 py-8 max-w-lg mx-auto">
      <h2 className="text-xl font-bold text-center">ConfiguraÃ§Ã£o de Rede</h2>

      {loading ? (
        <p className="text-center text-muted-foreground">Detectando informaÃ§Ãµes de rede...</p>
      ) : netInfo ? (
        <div className="space-y-3">
          <div className="flex items-center justify-between p-3 rounded-lg border">
            <span className="text-sm">Hostname</span>
            <span className="text-sm font-mono">{netInfo.hostname}</span>
          </div>
          <div className="flex items-center justify-between p-3 rounded-lg border">
            <span className="text-sm">IP Local</span>
            <span className="text-sm font-mono">{netInfo.ips[0] || '---'}</span>
          </div>
          <div className="flex items-center justify-between p-3 rounded-lg border">
            <span className="text-sm">Adaptador</span>
            <span className="text-sm truncate max-w-[200px]">{netInfo.adapter_name}</span>
          </div>
          <div className="flex items-center justify-between p-3 rounded-lg border">
            <span className="text-sm">Tipo de IP</span>
            <Badge variant={netInfo.is_static ? 'success' : 'warning'}>
              {netInfo.is_static ? 'Fixo' : 'DinÃ¢mico (DHCP)'}
            </Badge>
          </div>

          {!netInfo.is_static && (
            <div className="rounded-md bg-amber-50 border border-amber-200 p-3 text-sm">
              <p className="text-amber-800 font-medium">IP DinÃ¢mico Detectado</p>
              <p className="text-amber-700 text-xs mt-1">
                Para que outros computadores encontrem este servidor, recomenda-se IP fixo.
              </p>
              <Button
                onClick={handleSetStatic}
                disabled={configuring}
                variant="secondary"
                size="sm"
                className="mt-2"
              >
                {configuring ? 'Configurando...' : 'Configurar IP Fixo (recomendado)'}
              </Button>
              <p className="text-xs text-muted-foreground mt-1">
                IP: {netInfo.ips[0] || '---'} | Gateway: {netInfo.ips[0]?.split('.').slice(0, 3).join('.')}.1
              </p>
            </div>
          )}

          {netInfo.is_static && (
            <div className="rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-800">
              IP Fixo jÃ¡ configurado.
            </div>
          )}

          {suggestedUrl && (
            <div className="rounded-md bg-primary/10 border border-primary/20 p-3 text-sm">
              <p className="font-medium">URL sugerida para clientes:</p>
              <p className="font-mono text-xs mt-1 select-all">{suggestedUrl}</p>
            </div>
          )}
        </div>
      ) : (
        <p className="text-center text-destructive">NÃ£o foi possÃ­vel detectar informaÃ§Ãµes de rede.</p>
      )}

      <div className="space-y-1">
        <Button onClick={handleSetDhcp} disabled={configuring || !netInfo} variant="ghost" size="sm" className="text-xs">
          Reverter para DHCP
        </Button>
      </div>

      {msg && <p className="text-sm text-green-600 text-center">{msg}</p>}
      {err && <p className="text-sm text-destructive text-center">{err}</p>}

      {suggestedUrl && (
        <div className="rounded-md border p-3">
          <p className="text-sm font-medium mb-2">URL da API para este servidor:</p>
          <div className="flex gap-2">
            <input
              className="flex-1 rounded-md border px-3 py-2 text-sm font-mono"
              value={suggestedUrl}
              readOnly
              onClick={(e) => (e.target as HTMLInputElement).select()}
            />
            <Button
              variant="outline"
              size="sm"
              onClick={() => {
                navigator.clipboard.writeText(suggestedUrl)
                setMsg('URL copiada!')
              }}
            >
              Copiar
            </Button>
          </div>
          <Button
            className="mt-2 w-full"
            size="sm"
            onClick={() => {
              config.setApiUrl(suggestedUrl)
              config.setEnvironment('network')
              setMsg('URL salva na configuraÃ§Ã£o!')
            }}
          >
            Usar esta URL
          </Button>
        </div>
      )}

      <div className="flex justify-between">
        <Button variant="outline" onClick={onBack}>Voltar</Button>
        <Button onClick={onNext}>Continuar</Button>
      </div>
    </div>
  )
}

function TestStep({
  onNext,
  onBack,
  mode,
}: {
  onNext: () => void
  onBack: () => void
  mode: 'server' | 'client'
}) {
  const [testing, setTesting] = useState(false)
  const [result, setResult] = useState<'pending' | 'success' | 'fail'>('pending')
  const [msg, setMsg] = useState('')
  const config = useConfigStore()

  const handleTest = async () => {
    setTesting(true)
    setResult('pending')
    setMsg('')
    try {
      const ok = await healthCheck()
      if (ok) {
        setResult('success')
        setMsg('Backend conectado com sucesso!')
      } else {
        setResult('fail')
        setMsg('Backend nÃ£o respondeu. Verifique se os containers estÃ£o rodando.')
      }
    } catch {
      setResult('fail')
      setMsg('Erro de conexÃ£o.')
    }
    setTesting(false)
  }

  return (
    <div className="space-y-6 py-8 max-w-lg mx-auto">
      <h2 className="text-xl font-bold text-center">Testar ConexÃ£o</h2>

      <div className="rounded-md border p-4 text-sm space-y-2">
        <p className="flex justify-between">
          <span className="text-muted-foreground">Modo:</span>
          <Badge variant={mode === 'server' ? 'default' : 'secondary'}>
            {mode === 'server' ? 'Servidor' : 'Cliente'}
          </Badge>
        </p>
        <p className="flex justify-between">
          <span className="text-muted-foreground">URL da API:</span>
          <span className="font-mono text-xs">{config.apiUrl}</span>
        </p>
      </div>

      {result === 'success' && (
        <div className="rounded-md bg-green-50 border border-green-200 p-4 text-center">
          <div className="text-2xl mb-1">âœ…</div>
          <p className="text-green-800 font-medium">{msg}</p>
        </div>
      )}

      {result === 'fail' && (
        <div className="rounded-md bg-destructive/10 border border-destructive/20 p-4 text-center">
          <div className="text-2xl mb-1">âŒ</div>
          <p className="text-destructive font-medium">{msg}</p>
          <Button onClick={handleTest} variant="outline" size="sm" className="mt-2">
            Tentar Novamente
          </Button>
        </div>
      )}

      {result === 'pending' && (
        <div className="text-center">
          <Button onClick={handleTest} disabled={testing} size="lg">
            {testing ? 'Testando...' : 'Testar ConexÃ£o'}
          </Button>
        </div>
      )}

      <div className="flex justify-between">
        <Button variant="outline" onClick={onBack}>Voltar</Button>
        <Button onClick={onNext} disabled={result !== 'success'}>
          Concluir
        </Button>
      </div>
    </div>
  )
}

function DoneStep({ mode }: { mode: 'server' | 'client' }) {
  const navigate = useNavigate()
  const config = useConfigStore()

  const handleFinish = () => {
    config.setSetupCompleted(true)
    navigate('/')
  }

  return (
    <div className="space-y-6 py-8 text-center max-w-lg mx-auto">
      <div className="text-6xl mb-4">ðŸŽ‰</div>
      <h2 className="text-2xl font-bold">ConfiguraÃ§Ã£o ConcluÃ­da!</h2>
      <p className="text-muted-foreground">
        {mode === 'server'
          ? 'O servidor estÃ¡ pronto. Os containers Docker estÃ£o rodando e a rede estÃ¡ configurada.'
          : 'O cliente estÃ¡ configurado e conectado ao servidor.'}
      </p>

      {mode === 'server' && (
        <div className="rounded-md bg-primary/10 border border-primary/20 p-3 text-sm">
          <p className="font-medium">Para outros computadores:</p>
          <p className="text-xs text-muted-foreground mt-1">
            Copie o executÃ¡vel para as mÃ¡quinas da rede. Elas detectarÃ£o este servidor automaticamente.
          </p>
        </div>
      )}

      <Button onClick={handleFinish} size="lg">
        ComeÃ§ar a Usar
      </Button>
    </div>
  )
}

export function SetupPage() {
  const setup = useSetupStore()
  const [mode, setMode] = useState<'server' | 'client' | null>(null)
  const step = setup.currentStep

  const handleModeSelect = (selected: 'server' | 'client') => {
    setMode(selected)
    if (selected === 'server') {
      setup.setStep('docker')
    } else {
      setup.setStep('test')
    }
  }

  const steps = [
    { id: 'welcome', label: 'Boas-vindas' },
    { id: 'mode', label: 'Modo' },
    { id: 'docker', label: 'Docker' },
    { id: 'network', label: 'Rede' },
    { id: 'test', label: 'Teste' },
    { id: 'done', label: 'ConcluÃ­do' },
  ]
  const currentIndex = steps.findIndex((s) => s.id === step)

  return (
    <div className="min-h-screen flex items-center justify-center bg-muted p-4">
      <div className="w-full max-w-2xl">
        <div className="mb-8">
          <div className="flex items-center justify-center gap-1 mb-2">
            {steps.map((s, i) => (
              <div key={s.id} className="flex items-center">
                <div
                  className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-medium transition-colors ${
                    i <= currentIndex
                      ? 'bg-primary text-primary-foreground'
                      : 'bg-muted-foreground/20 text-muted-foreground'
                  }`}
                >
                  {i + 1}
                </div>
                {i < steps.length - 1 && (
                  <div
                    className={`w-8 h-0.5 transition-colors ${
                      i < currentIndex ? 'bg-primary' : 'bg-muted-foreground/20'
                    }`}
                  />
                )}
              </div>
            ))}
          </div>
          <p className="text-xs text-center text-muted-foreground">
            {steps[currentIndex]?.label || ''}
          </p>
        </div>

        <Card>
          <CardContent>
            {step === 'welcome' && (
              <WelcomeStep onNext={() => setup.setStep('mode')} />
            )}
            {step === 'mode' && (
              <ModeStep onNext={handleModeSelect} onBack={() => setup.setStep('welcome')} />
            )}
            {step === 'docker' && mode === 'server' && (
              <DockerCheckStep
                onNext={() => setup.setStep('network')}
                onBack={() => setup.setStep('mode')}
              />
            )}
            {step === 'network' && (
              <NetworkStep
                onNext={() => setup.setStep('test')}
                onBack={() => setup.setStep('docker')}
              />
            )}
            {step === 'test' && mode && (
              <TestStep
                onNext={() => setup.setStep('done')}
                onBack={() => setup.setStep(mode === 'server' ? 'network' : 'mode')}
                mode={mode}
              />
            )}
            {step === 'done' && mode && <DoneStep mode={mode} />}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}

