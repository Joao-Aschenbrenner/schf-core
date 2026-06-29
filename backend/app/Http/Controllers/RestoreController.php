<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\RestoreRequest;
use App\Models\Backup;
use App\Services\Backup\RestoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class RestoreController extends Controller
{
    protected RestoreService $restoreService;

    public function __construct(RestoreService $restoreService)
    {
        $this->restoreService = $restoreService;
    }

    public function restore(RestoreRequest $request, Backup $backup): JsonResponse
    {
        $password = $request->input('password');

        if (empty($password)) {
            return response()->json([
                'message' => 'Senha é obrigatória para restaurar backup'
            ], 422);
        }

        try {
            $result = $this->restoreService->restore($backup, auth()->user(), $password);

            return response()->json([
                'message' => 'Restauração concluída com sucesso',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro na restauração: ' . $e->getMessage()
            ], 500);
        }
    }

    public function validate(Backup $backup): JsonResponse
    {
        // Verificar se arquivo existe
        $exists = $backup->file_path && Storage::disk('local')->exists($backup->file_path);
        
        // Verificar integridade se arquivo existe
        $integrity = false;
        if ($exists) {
            try {
                $backupService = app(\App\Services\Backup\BackupService::class);
                $integrity = $backupService->verifyIntegrity($backup);
            } catch (\Exception $e) {
                $integrity = false;
            }
        }

        return response()->json([
            'file_exists' => $exists,
            'integrity_valid' => $integrity,
            'encrypted' => $backup->encrypted,
            'file_size' => $backup->file_size,
            'checksum' => $backup->checksum,
        ]);
    }
}
