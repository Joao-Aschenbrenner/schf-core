<?php

namespace App\Services\Migration;

use Illuminate\Support\Facades\Log;

class MigrationReport
{
    protected array $data = [];

    public function addStep(string $name, string $status, array $details = []): void
    {
        $this->data['steps'][] = [
            'name' => $name,
            'status' => $status,
            'details' => $details,
            'timestamp' => now()->toISOString(),
        ];
    }

    public function setSummary(array $summary): void
    {
        $this->data['summary'] = $summary;
    }

    public function setManifest(array $manifestInfo): void
    {
        $this->data['manifest'] = $manifestInfo;
    }

    public function setCompatibility(array $compatibility): void
    {
        $this->data['compatibility'] = $compatibility;
    }

    public function setValidation(array $validation): void
    {
        $this->data['validation'] = $validation;
    }

    public function setImportResult(array $result): void
    {
        $this->data['import'] = $result;
    }

    public function addError(string $message, array $context = []): void
    {
        $this->data['errors'][] = [
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];
    }

    public function addWarning(string $message, array $context = []): void
    {
        $this->data['warnings'][] = [
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];
    }

    public function toArray(): array
    {
        return array_merge([
            'generated_at' => now()->toISOString(),
            'version' => '1.0.0',
        ], $this->data);
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function toMarkdown(): string
    {
        $generatedAt = $this->data['generated_at'] ?? now()->toISOString();
        $md = "# Relatório de Migração\n\n";
        $md .= "**Gerado em:** {$generatedAt}\n\n";

        if (isset($this->data['manifest'])) {
            $m = $this->data['manifest'];
            $md .= "## Pacote\n\n";
            $md .= "- **Nome:** {$m['name']}\n";
            $md .= "- **Versão:** {$m['version']}\n";
            $md .= "- **Fonte:** {$m['source_type']}\n\n";
        }

        if (isset($this->data['compatibility'])) {
            $c = $this->data['compatibility'];
            $status = $c['compatible'] ? '✅ Compatível' : '❌ Incompatível';
            $md .= "## Compatibilidade\n\n{$status}\n\n";

            if (!empty($c['issues'])) {
                $md .= "**Problemas:**\n";
                foreach ($c['issues'] as $issue) {
                    $md .= "- {$issue}\n";
                }
                $md .= "\n";
            }

            if (!empty($c['warnings'])) {
                $md .= "**Avisos:**\n";
                foreach ($c['warnings'] as $warning) {
                    $md .= "- {$warning}\n";
                }
                $md .= "\n";
            }
        }

        if (isset($this->data['validation'])) {
            $v = $this->data['validation'];
            $md .= "## Validação\n\n";
            $md .= "- **Total de linhas:** {$v['total_rows']}\n";
            $md .= "- **Válidas:** {$v['valid_rows']}\n";
            $md .= "- **Inválidas:** {$v['invalid_rows']}\n";
            $md .= "- **Com avisos:** {$v['rows_with_warnings']}\n\n";
        }

        if (isset($this->data['import'])) {
            $i = $this->data['import'];
            $status = $i['success'] ? '✅ Sucesso' : '❌ Falha';
            $md .= "## Importação\n\n{$status}\n\n";
            $md .= "- **Importados:** {$i['imported']}\n";
            $md .= "- **Pulados:** {$i['skipped']}\n";
            $md .= "- **Falhas:** {$i['failed']}\n";
            $md .= "- **Duração:** {$i['duration_seconds']}s\n\n";
        }

        if (!empty($this->data['steps'])) {
            $md .= "## Etapas\n\n";
            $md .= "| Etapa | Status | Data |\n";
            $md .= "|-------|--------|------|\n";
            foreach ($this->data['steps'] as $step) {
                $md .= "| {$step['name']} | {$step['status']} | {$step['timestamp']} |\n";
            }
            $md .= "\n";
        }

        if (!empty($this->data['errors'])) {
            $md .= "## Erros\n\n";
            foreach ($this->data['errors'] as $error) {
                $md .= "- **{$error['timestamp']}**: {$error['message']}\n";
            }
            $md .= "\n";
        }

        if (!empty($this->data['warnings'])) {
            $md .= "## Avisos\n\n";
            foreach ($this->data['warnings'] as $warning) {
                $md .= "- **{$warning['timestamp']}**: {$warning['message']}\n";
            }
            $md .= "\n";
        }

        return $md;
    }

    public function save(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "migration_report_{$timestamp}";

        file_put_contents("{$directory}/{$filename}.json", $this->toJson());
        file_put_contents("{$directory}/{$filename}.md", $this->toMarkdown());

        Log::info('Relatório de migração salvo', ['directory' => $directory]);
    }
}