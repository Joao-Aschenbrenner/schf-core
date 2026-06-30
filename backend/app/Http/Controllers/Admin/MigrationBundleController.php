<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FeatureFlagService;
use App\Services\MigrationBundleImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MigrationBundleController extends Controller
{
    public function __construct(
        private MigrationBundleImportService $importer,
        private FeatureFlagService $features,
    ) {}

    public function validateBundle(Request $request): JsonResponse
    {
        if ($disabled = $this->disabledResponse()) {
            return $disabled;
        }

        $request->validate([
            'bundle' => ['required', 'file'],
        ]);

        return response()->json($this->importer->validate($request->file('bundle')));
    }

    public function preview(Request $request): JsonResponse
    {
        if ($disabled = $this->disabledResponse()) {
            return $disabled;
        }

        $request->validate([
            'bundle' => ['required', 'file'],
        ]);

        return response()->json($this->importer->preview($request->file('bundle')));
    }

    public function import(Request $request): JsonResponse
    {
        if ($disabled = $this->disabledResponse()) {
            return $disabled;
        }

        $request->validate([
            'bundle' => ['required', 'file'],
            'confirm' => ['accepted'],
        ]);

        return response()->json($this->importer->import(
            $request->file('bundle'),
            $request->user()?->id
        ));
    }

    private function disabledResponse(): ?JsonResponse
    {
        if ($this->features->enabled('migration_import')) {
            return null;
        }

        return response()->json([
            'error' => 'Migration import is disabled by feature flag.',
            'feature' => 'FEATURE_MIGRATION_IMPORT',
        ], 403);
    }
}
