<?php

namespace App\Plugins\Connectors;

use App\Plugins\BaseConnector;
use Illuminate\Support\Facades\Log;

class FirebirdConnectorPlugin extends BaseConnector
{
    protected $connection = null;

    public function getName(): string
    {
        return 'firebird-connector';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Conector para bancos de dados Firebird (legado)';
    }

    public function getAuthor(): string
    {
        return 'SCHF Team';
    }

    public function getSourceType(): string
    {
        return 'firebird';
    }

    public function getConfigSchema(): array
    {
        return [
            'host' => ['type' => 'string', 'required' => true, 'description' => 'Host do Firebird'],
            'port' => ['type' => 'integer', 'required' => false, 'default' => 3050, 'description' => 'Porta'],
            'database' => ['type' => 'string', 'required' => true, 'description' => 'Caminho do .fdb'],
            'username' => ['type' => 'string', 'required' => true, 'default' => 'SYSDBA', 'description' => 'Usuário'],
            'password' => ['type' => 'string', 'required' => true, 'description' => 'Senha'],
            'charset' => ['type' => 'string', 'required' => false, 'default' => 'UTF8', 'description' => 'Charset'],
        ];
    }

    protected function onInstall(): void
    {
        if (!extension_loaded('pdo_firebird')) {
            Log::info("Extensão pdo_firebird não encontrada. Instale php-pdo-firebird.");
        }
    }

    protected function onUninstall(): void
    {
        $this->disconnect();
    }

    protected function onConnect(array $config): void
    {
        $dsn = $this->buildDsn($config);
        $this->connection = new \PDO($dsn, $config['username'], $config['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
    }

    protected function onDisconnect(): void
    {
        $this->connection = null;
    }

    protected function onTestConnection(): array
    {
        if (!$this->connection) {
            throw new \RuntimeException('Não conectado');
        }

        $stmt = $this->connection->query('SELECT RDBGETCONTEXT(\'SERVER_VERSION\') FROM RDBDATABASE');
        $version = $stmt->fetchColumn();

        return [
            'server_version' => $version,
            'driver' => 'pdo_firebird',
        ];
    }

    public function query(string $sql, array $bindings = []): array
    {
        if (!$this->connection) {
            throw new \RuntimeException('Não conectado');
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function fetchAll(string $table, array $conditions = [], int $limit = 1000): array
    {
        $sql = "SELECT * FROM {$table}";
        $bindings = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $field => $value) {
                $where[] = "{$field} = ?";
                $bindings[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ROWS {$limit}";

        return $this->query($sql, $bindings);
    }

    public function fetchOne(string $table, array $conditions = []): ?array
    {
        $results = $this->fetchAll($table, $conditions, 1);
        return $results[0] ?? null;
    }

    public function getTables(): array
    {
        $sql = "SELECT RDBRELATION_NAME FROM RDBRELATIONS WHERE RDBSYSTEM_FLAG = 0 ORDER BY RDBRELATION_NAME";
        $results = $this->query($sql);
        return array_column($results, 'RDBRELATION_NAME');
    }

    public function getColumns(string $table): array
    {
        $sql = "SELECT RDBFIELD_NAME, RDBFIELD_TYPE, RDBFIELD_LENGTH
                FROM RDBRELATION_FIELDS RF
                JOIN RDBFIELDS F ON RF.RDBFIELD_SOURCE = F.RDBFIELD_NAME
                WHERE RF.RDBRELATION_NAME = ?
                ORDER BY RF.RDBFIELD_POSITION";
        return $this->query($sql, [$table]);
    }

    public function getTableCount(string $table): int
    {
        $result = $this->query("SELECT COUNT(*) AS cnt FROM {$table}");
        return (int) ($result[0]['cnt'] ?? 0);
    }

    protected function buildDsn(array $config): string
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 3050;
        $database = $config['database'];
        $charset = $config['charset'] ?? 'UTF8';

        return "firebird:dbname={$host}/{$port}:{$database};charset={$charset}";
    }
}