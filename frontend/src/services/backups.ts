import axios from 'axios';

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:9080/api';

export interface Backup {
  id: number;
  name: string;
  type: 'full' | 'database' | 'files';
  status: 'pending' | 'running' | 'completed' | 'failed' | 'restoring';
  file_path: string | null;
  file_name: string | null;
  file_size: number | null;
  checksum: string | null;
  encrypted: boolean;
  metadata: Record<string, unknown> | null;
  user_id: number;
  started_at: string | null;
  completed_at: string | null;
  error_message: string | null;
  created_at: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export const backupService = {
  async list(page = 1, perPage = 15, type?: string, status?: string): Promise<PaginatedResponse<Backup>> {
    const params = new URLSearchParams({ page: String(page), per_page: String(perPage) });
    if (type) params.set('type', type);
    if (status) params.set('status', status);
    const { data } = await axios.get(`${API_BASE}/admin/backups?${params}`);
    return data;
  },

  async create(type: 'full' | 'database' | 'files', password: string): Promise<{ backup_id: number; message: string }> {
    const { data } = await axios.post(`${API_BASE}/admin/backups`, { type, password });
    return data;
  },

  async show(id: number): Promise<{ backup: Backup }> {
    const { data } = await axios.get(`${API_BASE}/admin/backups/${id}`);
    return data;
  },

  async download(id: number): Promise<void> {
    const response = await axios.get(`${API_BASE}/admin/backups/${id}/download`, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `backup-${id}.zip`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  },

  async restore(id: number, password: string): Promise<{ message: string }> {
    const { data } = await axios.post(`${API_BASE}/admin/backups/${id}/restore`, { password });
    return data;
  },

  async destroy(id: number): Promise<{ message: string }> {
    const { data } = await axios.delete(`${API_BASE}/admin/backups/${id}`);
    return data;
  },

  async cleanup(): Promise<{ message: string }> {
    const { data } = await axios.post(`${API_BASE}/admin/backups/cleanup`);
    return data;
  },

  formatSize(bytes: number | null): string {
    if (!bytes) return '—';
    const units = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    let size = bytes;
    while (size >= 1024 && i < units.length - 1) {
      size /= 1024;
      i++;
    }
    return `${size.toFixed(1)} ${units[i]}`;
  },
};
