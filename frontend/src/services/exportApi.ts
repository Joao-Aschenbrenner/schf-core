import { api } from './api'
import type { ExportJob } from '../types'

export const exportService = {
  async createJob(data: { type: string; params?: Record<string, any> }): Promise<ExportJob> {
    const response = await api.post('/exports', data)
    return response.data.data
  },

  async getJob(id: number): Promise<ExportJob> {
    const response = await api.get(`/exports/${id}`)
    return response.data.data
  },

  async download(id: number): Promise<Blob> {
    const response = await api.get(`/exports/${id}/download`, {
      responseType: 'blob',
    })
    return response.data
  },

  async exportCsv(type: string, params?: Record<string, any>): Promise<void> {
    const job = await this.createJob({ type: `${type}_csv`, params })
    const completed = await this.pollJob(job.id)
    if (completed && completed.file_path) {
      const blob = await this.download(job.id)
      this.triggerDownload(blob, completed.file_name || `${type}.csv`)
    }
  },

  async exportXlsx(type: string, params?: Record<string, any>): Promise<void> {
    const job = await this.createJob({ type: `${type}_xlsx`, params })
    const completed = await this.pollJob(job.id)
    if (completed && completed.file_path) {
      const blob = await this.download(job.id)
      this.triggerDownload(blob, completed.file_name || `${type}.xlsx`)
    }
  },

  async pollJob(id: number, maxAttempts = 20, interval = 2000): Promise<ExportJob | null> {
    for (let i = 0; i < maxAttempts; i++) {
      const job = await this.getJob(id)
      if (job.status === 'completed') return job
      if (job.status === 'failed') throw new Error(job.error || 'Export failed')
      await new Promise((resolve) => setTimeout(resolve, interval))
    }
    return null
  },

  triggerDownload(blob: Blob, filename: string) {
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    a.remove()
    window.URL.revokeObjectURL(url)
  },
}
