<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackupRequest;
use App\Models\Backup;
use App\Services\Backup\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    public function index(Request $request): JsonResponse
    {
        $backups = Backup::with('user')
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($backups);
    }

    public function store(BackupRequest $request): JsonResponse
    {
        $type = $request->input('type', 'full');
        $password = $request->input('password');

        if (empty($password)) {
            return response()->json([
                'message' => 'Senha é obrigatória para criar backup'
            ], 422);
        }

        try {
            $backup = match($type) {
                'full' => $this->backupService->createFullBackup(auth()->user(), $password),
                'database' => $this->backupService->createDatabaseBackup(auth()->user(), $password),
                'files' => $this->backupService->createFilesBackup(auth()->user(), $password),
                default => throw new \InvalidArgumentException('Tipo de backup inválido')
            };

            return response()->json(['data' => $backup->load('user')], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar backup: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Backup $backup): JsonResponse
    {
        return response()->json(['data' => $backup->load('user')]);
    }

    public function download(Backup $backup)
    {
        if (!$backup->file_path || $backup->status !== 'completed') {
            abort(404, 'Arquivo não disponível');
        }

        $path = Storage::disk('local')->path($backup->file_path);
        
        if (!Storage::disk('local')->exists($backup->file_path)) {
            abort(404, 'Arquivo não encontrado');
        }

        return response()->download($path, $backup->file_name);
    }

    public function destroy(Backup $backup): JsonResponse
    {
        $filePath = $backup->file_path;
        
        if ($filePath && Storage::disk('local')->exists($filePath)) {
            Storage::disk('local')->delete($filePath);
        }
        
        $backup->delete();

        return response()->json(['message' => 'Backup excluído com sucesso']);
    }

    public function cleanup(): JsonResponse
    {
        $deleted = $this->backupService->cleanupOldBackups();
        
        return response()->json([
            'message' => 'Limpeza concluída',
            'deleted' => $deleted,
        ]);
    }

    public function verify(Backup $backup): JsonResponse
    {
        $isValid = $this->backupService->verifyIntegrity($backup);
        
        return response()->json([
            'valid' => $isValid,
            'checksum' => $backup->checksum,
        ]);
    }

    public function export(Backup $backup): JsonResponse
    {
        $filePath = storage_path('app/' . $backup->file_path);
        
        if (!file_exists($filePath)) {
            abort(404, 'Arquivo não encontrado');
        }

        return response()->download($filePath, $backup->file_name);
    }
}
