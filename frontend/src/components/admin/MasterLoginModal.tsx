import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { X } from 'lucide-react'
import { masterAuthService } from '@/services/adminApi'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Label } from '@/components/ui/Label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { useConfigStore } from '@/stores/configStore'

interface MasterLoginModalProps {
  onClose: () => void
}

export function MasterLoginModal({ onClose }: MasterLoginModalProps) {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)
  const navigate = useNavigate()
  const { backendStatus } = useConfigStore()

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)
    setLoading(true)

    try {
      await masterAuthService.login(email, password)
      onClose()
      navigate('/admin')
    } catch (err: any) {
      if (err.response?.status === 401) {
        setError('Credenciais invÃ¡lidas ou acesso negado.')
      } else if (err.response?.status === 403) {
        setError('Este usuÃ¡rio nÃ£o possui privilÃ©gios MASTER.')
      } else if (!err.response) {
        setError('NÃ£o foi possÃ­vel conectar ao servidor.')
      } else {
        setError('Erro ao fazer login master. Tente novamente.')
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
      <div className="absolute inset-0" onClick={onClose} />
      <div className="relative z-10 w-full max-w-sm">
        <Card className="shadow-2xl border-2 border-primary/20">
          <CardHeader className="pb-3">
            <div className="flex items-center justify-between">
              <CardTitle className="text-lg flex items-center gap-2">
                <span className="text-xl">âš™ï¸</span>
                Painel Administrativo
              </CardTitle>
              <button
                onClick={onClose}
                className="p-1 rounded-md hover:bg-muted transition-colors"
              >
                <X className="w-4 h-4 text-muted-foreground" />
              </button>
            </div>
            <p className="text-xs text-muted-foreground mt-1">
              Acesso restrito a usuÃ¡rios MASTER
            </p>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              {error && (
                <div className="rounded-md bg-destructive/10 border border-destructive/20 p-3">
                  <p className="text-sm text-destructive">{error}</p>
                </div>
              )}

              <div className="space-y-1.5">
                <Label htmlFor="master-email">Email MASTER</Label>
                <Input
                  id="master-email"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="admin@hospital.local"
                  required
                  disabled={loading || backendStatus !== 'online'}
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="master-password">Senha</Label>
                <Input
                  id="master-password"
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢"
                  required
                  disabled={loading || backendStatus !== 'online'}
                />
              </div>

              <Button
                type="submit"
                disabled={loading || backendStatus !== 'online'}
                className="w-full"
              >
                {loading ? 'Verificando...' : 'Acessar Painel'}
              </Button>

              <Button
                type="button"
                variant="ghost"
                onClick={onClose}
                className="w-full text-xs"
              >
                Cancelar
              </Button>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
