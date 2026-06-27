<?php

namespace App\Plugins;

interface ConnectorInterface extends PluginInterface
{
    public function connect(array $config): bool;

    public function disconnect(): bool;

    public function isConnected(): bool;

    public function testConnection(): array;

    public function query(string $sql, array $bindings = []): array;

    public function fetchAll(string $table, array $conditions = [], int $limit = 1000): array;

    public function fetchOne(string $table, array $conditions = []): ?array;

    public function getTables(): array;

    public function getColumns(string $table): array;

    public function getTableCount(string $table): int;

    public function getSourceType(): string;

    public function getConfigSchema(): array;
}