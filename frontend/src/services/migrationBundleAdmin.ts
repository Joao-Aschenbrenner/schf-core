import { adminApi } from './adminApi'

export interface MigrationBundleValidation {
  valid: boolean
  manifest?: Record<string, any>
  summary?: Record<string, number>
  warnings: string[]
  errors: string[]
}

export interface MigrationBundleImportResult extends MigrationBundleValidation {
  imported?: Record<string, number | string>
  skipped?: Record<string, number>
  operator_id?: number | null
}

function formData(file: File, confirm = false) {
  const data = new FormData()
  data.append('bundle', file)
  if (confirm) {
    data.append('confirm', '1')
  }
  return data
}

const multipart = {
  headers: { 'Content-Type': 'multipart/form-data' },
}

export const migrationBundleAdminService = {
  validate(file: File) {
    return adminApi.post<MigrationBundleValidation>('/migration/bundles/validate', formData(file), multipart)
  },

  preview(file: File) {
    return adminApi.post<MigrationBundleValidation>('/migration/bundles/preview', formData(file), multipart)
  },

  import(file: File) {
    return adminApi.post<MigrationBundleImportResult>('/migration/bundles/import', formData(file, true), multipart)
  },
}
