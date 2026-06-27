<?php

namespace App\Jobs;

use App\Services\Migration\ImportEngine;
use App\Services\Migration\MigrationManifest;
use Illuminate\Support\Facades\Log;

class ImportJob extends BaseJob
{
    public string $jobType = 'import';

    public function __construct(
        protected string $packagePath,
        protected string $filePath
    ) {
        parent::__construct();
        $this->timeout = 600;
    }

    public function handle(): array
    {
        Log::info("Iniciando ImportJob", [
            'package' => $this->packagePath,
            'file' => $this->filePath,
        ]);

        $manifest = MigrationManifest::fromPackage($this->packagePath);
        if (!$manifest) {
            return ['success' => false, 'message' => 'Manifest não encontrado'];
        }

        $importer = app(ImportEngine::class);
        $result = $importer->importFile($manifest, $this->filePath);

        return $result;
    }
}