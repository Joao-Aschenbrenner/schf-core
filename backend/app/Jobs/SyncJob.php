<?php

namespace App\Jobs;

use App\Plugins\ConnectorInterface;
use App\Plugins\PluginLoader;
use Illuminate\Support\Facades\Log;

class SyncJob extends BaseJob
{
    public string $jobType = 'sync';

    public function __construct(
        protected string $sourceType,
        protected array $tables = [],
        protected array $config = []
    ) {
        parent::__construct();
        $this->timeout = 1200;
    }

    public function handle(): array
    {
        Log::info("Iniciando SyncJob", [
            'source' => $this->sourceType,
            'tables' => $this->tables,
        ]);

        $loader = app(PluginLoader::class);
        $connector = $loader->loadConnector($this->sourceType);

        if (!$connector) {
            return ['success' => false, 'message' => "Conector não encontrado: {$this->sourceType}"];
        }

        if (!$connector instanceof ConnectorInterface) {
            return ['success' => false, 'message' => "Plugin não é um conector válido"];
        }

        if (!$connector->connect($this->config)) {
            return ['success' => false, 'message' => 'Falha ao conectar na fonte de dados'];
        }

        try {
            $tables = $this->tables ?: $connector->getTables();
            $synced = 0;
            $errors = [];

            foreach ($tables as $table) {
                try {
                    $data = $connector->fetchAll($table);
                    $synced += count($data);
                    Log::info("Tabela sincronizada", ['table' => $table, 'rows' => count($data)]);
                } catch (\Exception $e) {
                    $errors[$table] = $e->getMessage();
                    Log::error("Erro ao sincronizar tabela", ['table' => $table, 'error' => $e->getMessage()]);
                }
            }

            $connector->disconnect();

            return [
                'success' => empty($errors),
                'tables_synced' => count($tables) - count($errors),
                'total_rows' => $synced,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            $connector->disconnect();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}