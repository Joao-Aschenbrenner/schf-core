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
        $result = $this->updateService->check();
        return response()->json($result);
    }

    public function checkVersion(string $version)
    {
        $result = $this->updateService->checkSpecific($version);
        return response()->json($result);
    }

    public function versions()
    {
        $versions = $this->updateService->versions();
        return response()->json(['versions' => $versions]);
    }

    public function download(string $version)
    {
        $result = $this->updateService->download($version);
        return response()->json($result);
    }

    public function verify(string $version)
    {
        $result = $this->updateService->verify($version);
        return response()->json($result);
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
        $currentVersion = app(\App\Services\VersionChecker::class)->getCurrentVersion();
        $changelog = $this->updateService->getChangelog($currentVersion);
        return response()->json([
            'current_version' => $currentVersion,
            'changelog' => $changelog,
        ]);
    }

    public function history()
    {
        $history = $this->updateService->history();
        return response()->json(['history' => $history]);
    }
}