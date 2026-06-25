import { create } from 'zustand'
import type { SetupStep } from '../types/tauri'

interface SetupState {
  currentStep: SetupStep
  dockerInstalled: boolean
  dockerRunning: boolean
  hasContainers: boolean
  containersStarted: boolean
  networkConfigured: boolean
  backendOnline: boolean
  error: string | null
  setStep: (step: SetupStep) => void
  setDockerInstalled: (v: boolean) => void
  setDockerRunning: (v: boolean) => void
  setHasContainers: (v: boolean) => void
  setContainersStarted: (v: boolean) => void
  setNetworkConfigured: (v: boolean) => void
  setBackendOnline: (v: boolean) => void
  setError: (err: string | null) => void
  reset: () => void
}

const initial: SetupState = {
  currentStep: 'welcome',
  dockerInstalled: false,
  dockerRunning: false,
  hasContainers: false,
  containersStarted: false,
  networkConfigured: false,
  backendOnline: false,
  error: null,
  setStep: () => {},
  setDockerInstalled: () => {},
  setDockerRunning: () => {},
  setHasContainers: () => {},
  setContainersStarted: () => {},
  setNetworkConfigured: () => {},
  setBackendOnline: () => {},
  setError: () => {},
  reset: () => {},
}

export const useSetupStore = create<SetupState>()((set) => ({
  ...initial,
  setStep: (step) => set({ currentStep: step, error: null }),
  setDockerInstalled: (v) => set({ dockerInstalled: v }),
  setDockerRunning: (v) => set({ dockerRunning: v }),
  setHasContainers: (v) => set({ hasContainers: v }),
  setContainersStarted: (v) => set({ containersStarted: v }),
  setNetworkConfigured: (v) => set({ networkConfigured: v }),
  setBackendOnline: (v) => set({ backendOnline: v }),
  setError: (err) => set({ error: err }),
  reset: () => set(initial),
}))
