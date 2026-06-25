import { invoke } from '@tauri-apps/api/core'
import type { NetworkInfo, DiscoveryResult, ContainerInfo, TunnelInfo } from '../types/tauri'

export const tauriApi = {
  checkDockerInstalled: (): Promise<boolean> => invoke('check_docker_installed'),
  checkDockerRunning: (): Promise<boolean> => invoke('check_docker_running'),
  getContainers: (dockerDir: string): Promise<ContainerInfo[]> => invoke('get_containers', { dockerDir }),
  dockerComposeUp: (dockerDir: string): Promise<string> => invoke('docker_compose_up', { dockerDir }),
  dockerComposeDown: (dockerDir: string): Promise<string> => invoke('docker_compose_down', { dockerDir }),

  getNetworkInfo: (): Promise<NetworkInfo> => invoke('get_network_info'),
  setStaticIp: (ip: string, mask: string, gateway: string, adapter: string): Promise<string> =>
    invoke('set_static_ip', { ip, mask, gateway, adapter }),
  setDynamicIp: (adapter: string): Promise<string> => invoke('set_dynamic_ip', { adapter }),

  startDiscoveryServer: (): Promise<void> => invoke('start_discovery_server'),
  stopDiscoveryServer: (): Promise<void> => invoke('stop_discovery_server'),
  discoverServers: (): Promise<DiscoveryResult[]> => invoke('discover_servers'),

  getAppDataDir: (): Promise<string> => invoke('get_app_data_dir'),
  ping: (): Promise<string> => invoke('ping'),

  // Tunnel
  checkCloudflared: (): Promise<boolean> => invoke('check_cloudflared'),
  checkNgrok: (): Promise<boolean> => invoke('check_ngrok'),
  startTunnel: (tunnelType: string, localUrl: string, token: string): Promise<string> =>
    invoke('start_tunnel', { tunnelType, localUrl, token }),
  stopTunnel: (): Promise<void> => invoke('stop_tunnel'),
  getTunnelInfo: (): Promise<TunnelInfo> => invoke('get_tunnel_info'),
}
