export interface ContainerInfo {
  name: string
  status: string
}

export interface NetworkInfo {
  hostname: string
  ips: string[]
  adapter_name: string
  is_static: boolean
  gateway: string
  subnet_mask: string
}

export interface DiscoveryResult {
  hostname: string
  ip: string
  port: number
}

export interface TunnelInfo {
  running: boolean
  url: string
  tunnel_type: string
}

export type ServerMode = 'none' | 'server' | 'client'
export type SetupStep = 'welcome' | 'mode' | 'docker' | 'network' | 'containers' | 'test' | 'done'
