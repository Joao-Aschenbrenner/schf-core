export function formatCurrency(value: number | string | null | undefined): string {
  const num = typeof value === 'string' ? parseFloat(value) : value
  if (num === null || num === undefined || isNaN(num)) return 'R$ 0,00'

  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(num)
}

export function formatDate(date: string | null | undefined): string {
  if (!date) return '-'
  return new Intl.DateTimeFormat('pt-BR').format(new Date(date))
}

export function formatCnpj(cnpj: string | null | undefined): string {
  if (!cnpj) return '-'
  const clean = cnpj.replace(/\D/g, '')
  if (clean.length !== 14) return cnpj
  return clean.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5')
}

export function formatCpf(cpf: string | null | undefined): string {
  if (!cpf) return '-'
  const clean = cpf.replace(/\D/g, '')
  if (clean.length !== 11) return cpf
  return clean.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
}
