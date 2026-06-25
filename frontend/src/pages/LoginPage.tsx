import { useState, useEffect } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import { useConfigStore } from '../stores/configStore'
import { healthCheck } from '../services/api'
import { Button } from '../components/ui/Button'
import { Input } from '../components/ui/Input'
import { Label } from '../components/ui/Label'
import { Badge } from '../components/ui/Badge'
import { MasterLoginModal } from '../components/admin/MasterLoginModal'

export function LoginPage() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [showMasterModal, setShowMasterModal] = useState(false)
  const { login } = useAuth()
  const navigate = useNavigate()
  const { backendStatus, apiUrl } = useConfigStore()

  useEffect(() => {
    healthCheck()
  }, [])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)

    try {
      await login.mutateAsync({ email, password })
      navigate('/')
    } catch (err: any) {
      if (err.response?.status === 422) {
        setError('Credenciais invÃ¡lidas. Verifique e-mail e senha.')
      } else if (!err.response) {
        setError('NÃ£o foi possÃ­vel conectar ao servidor. Verifique a conexÃ£o.')
      } else {
        setError('Erro ao fazer login. Tente novamente.')
      }
    }
  }

  const isBackendDown = backendStatus === 'offline'
  const isChecking = backendStatus === 'checking'

  return (
    <>
      <form onSubmit={handleSubmit} className="space-y-4 bg-card p-6 rounded-lg border shadow-sm">
        <div className="flex items-center justify-between mb-2">
          <span className="text-xs text-muted-foreground">Servidor:</span>
          <div className="flex items-center gap-2">
            <Badge variant={backendStatus === 'online' ? 'success' : 'destructive'}>
              {isChecking ? 'Verificando...' : backendStatus === 'online' ? 'Online' : 'Offline'}
            </Badge>
          </div>
        </div>

        {isBackendDown && (
          <div className="rounded-md bg-destructive/10 border border-destructive/20 p-3 mb-2">
            <p className="text-sm text-destructive">Servidor indisponÃ­vel</p>
            <Link to="/conexao" className="text-xs text-primary underline">
              Configurar conexÃ£o
            </Link>
          </div>
        )}

        {error && (
          <div className="rounded-md bg-destructive/10 border border-destructive/20 p-3">
            <p className="text-sm text-destructive">{error}</p>
          </div>
        )}

        <div>
          <Label htmlFor="email">E-mail</Label>
          <Input
            id="email"
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="usuario@hospital.com"
            required
            disabled={isBackendDown}
          />
        </div>
        <div>
          <Label htmlFor="password">Senha</Label>
          <Input
            id="password"
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="Digite sua senha"
            required
            disabled={isBackendDown}
          />
        </div>
        <Button
          type="submit"
          disabled={login.isPending || isBackendDown}
          className="w-full"
        >
          {login.isPending ? 'Entrando...' : 'Entrar'}
        </Button>

        <Button
          type="button"
          variant="outline"
          onClick={() => healthCheck()}
          disabled={isChecking}
          className="w-full"
        >
          {isChecking ? 'Verificando...' : 'Testar ConexÃ£o'}
        </Button>

        <p className="text-xs text-center text-muted-foreground">
          API: {apiUrl}
        </p>
      </form>

      {/* Gear icon - always visible, even when backend is down */}
      <button
        onClick={() => setShowMasterModal(true)}
        className="fixed bottom-4 right-4 z-40 p-3 rounded-full bg-primary/10 hover:bg-primary/20 border border-primary/30 transition-all shadow-md group"
        title="Painel Administrativo MASTER"
      >
        <span className="text-xl group-hover:rotate-45 transition-transform duration-300">âš™ï¸</span>
      </button>

      {showMasterModal && (
        <MasterLoginModal onClose={() => setShowMasterModal(false)} />
      )}
    </>
  )
}

