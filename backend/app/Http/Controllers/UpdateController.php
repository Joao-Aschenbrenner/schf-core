<?php

namespace App\Http\Controllers;

use App\Services\UpdateService;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    protected UpdateService $updateService;

    public function __construct(UpdateService $updateService)
    {
        $this->updateService = $updateService;
    }

    public function check()
    {
        $currentVersion = $this->getCurrentVersion();
        $latestRelease = $this->updateService->fetchLatestRelease();

        if (!$latestRelease) {
            return response()->json([
                'current_version' => $currentVersion,
                'latest_version' => null,
                'update_available' => false,
                'message' => 'Não foi possível verificar atualizações',
            ]);
        }

        $updateAvailable = version_compare($currentVersion, $latestRelease['tag_name'], '<');

        return response()->json([
            'current_version' => $currentVersion,
            'latest_version' => $latestRelease['tag_name'],
            'update_available' => $updateAvailable,
            'release_notes' => $latestRelease['body'] ?? '',
            'published_at' => $latestRelease['published_at'] ?? null,
            'html_url' => $latestRelease['html_url'] ?? null,
        ]);
    }

    public function run(Request $request)
    {
        $targetVersion = $request->input('version');

        $result = $this->updateService->runUpdate($targetVersion);

        return response()->json($result);
    }

    public function rollback()
    {
        $result = $this->updateService->rollback();

        return response()->json($result);
    }

    public function changelog()
    {
        $currentVersion = $this->getCurrentVersion();
        $changelog = $this->updateService->getChangelog($currentVersion);

        return response()->json([
            'current_version' => $currentVersion,
            'changelog' => $changelog,
        ]);
    }

    private function getCurrentVersion(): string
    {
        $composerPath = base_path('composer.json');
        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            return $composer['version'] ?? '0.0.0';
        }
        return '0.0.0';
    }
}