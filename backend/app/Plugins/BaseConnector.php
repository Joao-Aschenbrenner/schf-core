<?php

namespace App\Plugins;

use Illuminate\Support\Facades\Log;

abstract class BaseConnector extends BasePlugin implements ConnectorInterface
{
    protected bool $connected = false;
    protected array $config = [];

    public function connect(array $config): bool
    {
        try {
            $this->config = $config;
            $this->onConnect($config);
            $this->connected = true;
            Log::info("Conector conectado", ['name' => $this->getName()]);
            return true;
        } catch (\Exception $e) {
            Log::error("Erro ao conectar", ['name' => $this->getName(), 'error' => $e->getMessage()]);
            $this->connected = false;
            return false;
        }
    }

    public function disconnect(): bool
    {
        try {
            $this->onDisconnect();
            $this->connected = false;
            $this->config = [];
            Log::info("Conector desconectado", ['name' => $this->getName()]);
            return true;
        } catch (\Exception $e) {
            Log::error("Erro ao desconectar", ['name' => $this->getName(), 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function testConnection(): array
    {
        try {
            $result = $this->onTestConnection();
            return [
                'success' => true,
                'message' => 'Conexão testada com sucesso',
                'details' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Falha no teste de conexão: ' . $e->getMessage(),
            ];
        }
    }

    abstract protected function onConnect(array $config): void;

    abstract protected function onDisconnect(): void;

    abstract protected function onTestConnection(): array;
}