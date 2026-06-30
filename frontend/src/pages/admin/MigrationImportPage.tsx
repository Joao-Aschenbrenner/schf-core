import { useState } from 'react'
import { AlertCircle, CheckCircle, FileArchive, Upload } from 'lucide-react'
import { migrationBundleAdminService, MigrationBundleImportResult, MigrationBundleValidation } from '../../services/migrationBundleAdmin'

type Action = 'validate' | 'preview' | 'import'

export default function MigrationImportPage() {
  const [file, setFile] = useState<File | null>(null)
  const [loading, setLoading] = useState<Action | null>(null)
  const [result, setResult] = useState<MigrationBundleValidation | MigrationBundleImportResult | null>(null)

  const run = async (action: Action) => {
    if (!file) return

    if (action === 'import' && !confirm('Import this Migration Bundle into SCHF Core?')) {
      return
    }

    setLoading(action)
    try {
      const response = action === 'validate'
        ? await migrationBundleAdminService.validate(file)
        : action === 'preview'
          ? await migrationBundleAdminService.preview(file)
          : await migrationBundleAdminService.import(file)

      setResult(response.data)
    } catch (error: any) {
      setResult({
        valid: false,
        warnings: [],
        errors: [error.response?.data?.error ?? error.message ?? 'Unexpected import error'],
      })
    } finally {
      setLoading(null)
    }
  }

  return (
    <div className="p-6 max-w-5xl mx-auto space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Migração</h1>
        <p className="text-muted-foreground mt-1">
          Importe apenas Migration Bundles gerados pelo SCHF Migration. O Core nao conhece o banco legado.
        </p>
      </div>

      <div className="bg-card border rounded-lg p-6 space-y-4">
        <div className="flex items-center gap-3">
          <FileArchive className="w-6 h-6 text-primary" />
          <div>
            <h2 className="font-semibold">Selecionar Bundle</h2>
            <p className="text-sm text-muted-foreground">Arquivo esperado: <code>migration-package.schf</code></p>
          </div>
        </div>

        <input
          type="file"
          accept=".schf"
          onChange={(event) => setFile(event.target.files?.[0] ?? null)}
          className="block w-full text-sm"
        />

        <div className="flex flex-wrap gap-3">
          <button
            disabled={!file || loading !== null}
            onClick={() => run('validate')}
            className="px-4 py-2 rounded-md bg-muted text-foreground hover:bg-muted/80 disabled:opacity-50"
          >
            {loading === 'validate' ? 'Validando...' : 'Validar'}
          </button>
          <button
            disabled={!file || loading !== null}
            onClick={() => run('preview')}
            className="px-4 py-2 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
          >
            {loading === 'preview' ? 'Gerando preview...' : 'Preview'}
          </button>
          <button
            disabled={!file || loading !== null || !result?.valid}
            onClick={() => run('import')}
            className="px-4 py-2 rounded-md bg-green-600 text-white hover:bg-green-700 disabled:opacity-50 inline-flex items-center gap-2"
          >
            <Upload className="w-4 h-4" />
            {loading === 'import' ? 'Importando...' : 'Importar Bundle'}
          </button>
        </div>
      </div>

      {result && (
        <div className="bg-card border rounded-lg p-6 space-y-5">
          <div className="flex items-center gap-3">
            {result.valid ? (
              <CheckCircle className="w-6 h-6 text-green-600" />
            ) : (
              <AlertCircle className="w-6 h-6 text-red-600" />
            )}
            <div>
              <h2 className="font-semibold">Resultado</h2>
              <p className="text-sm text-muted-foreground">{result.valid ? 'Bundle valido' : 'Bundle invalido'}</p>
            </div>
          </div>

          {result.summary && (
            <div>
              <h3 className="font-medium mb-2">Resumo</h3>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                {Object.entries(result.summary).map(([name, count]) => (
                  <div key={name} className="border rounded-md p-3">
                    <p className="text-xs text-muted-foreground truncate">{name}</p>
                    <p className="text-lg font-semibold">{count}</p>
                  </div>
                ))}
              </div>
            </div>
          )}

          {'imported' in result && result.imported && (
            <div>
              <h3 className="font-medium mb-2">Importado</h3>
              <pre className="bg-muted rounded-md p-3 text-sm overflow-auto">{JSON.stringify(result.imported, null, 2)}</pre>
            </div>
          )}

          {result.warnings.length > 0 && (
            <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
              <h3 className="font-medium text-yellow-900 mb-2">Avisos</h3>
              <ul className="text-sm text-yellow-800 space-y-1">
                {result.warnings.map((warning) => <li key={warning}>{warning}</li>)}
              </ul>
            </div>
          )}

          {result.errors.length > 0 && (
            <div className="bg-red-50 border border-red-200 rounded-md p-4">
              <h3 className="font-medium text-red-900 mb-2">Erros</h3>
              <ul className="text-sm text-red-800 space-y-1">
                {result.errors.map((error) => <li key={error}>{error}</li>)}
              </ul>
            </div>
          )}
        </div>
      )}
    </div>
  )
}
