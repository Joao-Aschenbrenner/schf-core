import { useState, useEffect } from 'react'
import { Download, RefreshCw, RotateCcw, AlertCircle, CheckCircle, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { updateApi, UpdateCheck, UpdateResult } from '@/services/updateApi'

export function UpdatePage() {
  const [updateInfo, setUpdateInfo] = useState<UpdateCheck | null>(null)
  const [changelog, setChangelog] = useState<string>('')
  const [actionLoading, setActionLoading] = useState<'check' | 'update' | 'rollback' | null>(null)
  const [result, setResult] = useState<UpdateResult | null>(null)
  const [error, setError] = useState<string | null>(null)

  const checkUpdates = async () => {
    setActionLoading('check')
    setError(null)
    try {
      const data = await updateApi.check()
      setUpdateInfo(data)
      if (data.update_available) {
        const cl = await updateApi.changelog()
        setChangelog(cl.changelog)
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erro ao verificar atualizações')
    } finally {
      setActionLoading(null)
    }
  }

  const runUpdate = async () => {
    if (!updateInfo?.latest_version) return
    setActionLoading('update')
    setError(null)
    setResult(null)
    try {
      const data = await updateApi.run(updateInfo.latest_version)
      setResult(data)
      if (data.success) {
        await checkUpdates()
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erro ao executar atualização')
    } finally {
      setActionLoading(null)
    }
  }

  const runRollback = async () => {
    setActionLoading('rollback')
    setError(null)
    setResult(null)
    try {
      const data = await updateApi.rollback()
      setResult(data)
      if (data.success) {
        await checkUpdates()
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erro ao fazer rollback')
    } finally {
      setActionLoading(null)
    }
  }

  useEffect(() => {
    checkUpdates()
  }, [])

  const formatDate = (dateStr: string | null) => {
    if (!dateStr) return 'N/A'
    return new Date(dateStr).toLocaleString('pt-BR')
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Atualizações do Sistema</h1>
          <p className="text-gray-500">Gerencie versões e atualizações do SCHF</p>
        </div>
        <Button variant="outline" onClick={checkUpdates} disabled={actionLoading === 'check'}>
          <RefreshCw className={`w-4 h-4 mr-2 ${actionLoading === 'check' ? 'animate-spin' : ''}`} />
          Verificar
        </Button>
      </div>

      {error && (
        <div className="p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2 text-red-700">
          <AlertCircle className="w-5 h-5 flex-shrink-0" />
          <span>{error}</span>
        </div>
      )}

      {result && (
        <div className={`p-4 rounded-lg flex items-center gap-2 ${
          result.success ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'
        }`}>
          {result.success ? <CheckCircle className="w-5 h-5 flex-shrink-0" /> : <AlertCircle className="w-5 h-5 flex-shrink-0" />}
          <span>{result.message}</span>
        </div>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Status da Versão</CardTitle>
          <CardDescription>Versão atual e disponibilidade de atualizações</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="p-4 bg-gray-50 rounded-lg">
              <label className="text-sm font-medium text-gray-500">Versão Atual</label>
              <p className="text-2xl font-bold text-gray-900 mt-1">{updateInfo?.current_version || 'Carregando...'}</p>
            </div>
            <div className="p-4 bg-gray-50 rounded-lg">
              <label className="text-sm font-medium text-gray-500">Última Versão</label>
              <p className="text-2xl font-bold text-gray-900 mt-1">{updateInfo?.latest_version || 'Verificando...'}</p>
            </div>
          </div>

          <div className="flex items-center gap-4">
            {updateInfo?.update_available ? (
              <>
                <Badge className="bg-green-100 text-green-800 px-3 py-1 text-sm">
                  <CheckCircle className="w-3 h-3 mr-1 inline" />
                  Atualização Disponível
                </Badge>
                <span className="text-sm text-gray-500">Publicada em: {formatDate(updateInfo.published_at)}</span>
              </>
            ) : (
              <Badge className="bg-blue-100 text-blue-800 px-3 py-1 text-sm">
                <CheckCircle className="w-3 h-3 mr-1 inline" />
                Sistema Atualizado
              </Badge>
            )}
          </div>

          <div className="flex gap-3">
            {updateInfo?.update_available && (
              <Button
                onClick={runUpdate}
                disabled={actionLoading === 'update' || actionLoading === 'rollback'}
              >
                {actionLoading === 'update' ? (
                  <>
                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                    Atualizando...
                  </>
                ) : (
                  <>
                    <Download className="w-4 h-4 mr-2" />
                    Atualizar para {updateInfo.latest_version}
                  </>
                )}
              </Button>
            )}

            <Button
              variant="outline"
              onClick={runRollback}
              disabled={actionLoading === 'update' || actionLoading === 'rollback'}
            >
              {actionLoading === 'rollback' ? (
                <>
                  <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                  Revertendo...
                </>
              ) : (
                <>
                  <RotateCcw className="w-4 h-4 mr-2" />
                  Rollback
                </>
              )}
            </Button>
          </div>
        </CardContent>
      </Card>

      {changelog && (
        <Card>
          <CardHeader>
            <CardTitle>Notas de Release</CardTitle>
            <CardDescription>Alterações na versão {updateInfo?.latest_version}</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap">
              {changelog}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
