<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\AuditTrailController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AuditTrail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|integer',
            'action' => 'nullable|string|max:255',
            'model_type' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = DB::table('activity_log')
            ->select([
                'activity_log.*',
                'users.name as user_name',
                'users.email as user_email',
            ])
            ->leftJoin('users', 'activity_log.causer_id', '=', 'users.id')
            ->orderBy('activity_log.created_at', 'desc');

        if ($request->filled('user_id')) {
            $query->where('activity_log.causer_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('activity_log.description', 'like', '%' . $request->action . '%');
        }

        if ($request->filled('model_type')) {
            $query->where('activity_log.subject_type', 'like', '%' . $request->model_type . '%');
        }

        if ($request->filled('date_from')) {
            $query->where('activity_log.created_at', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('activity_log.created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $perPage = $request->input('per_page', 50);
        $results = $query->paginate($perPage);

        return response()->json($results);
    }

    public function export(Request $request, string $format): JsonResponse|StreamedResponse
    {
        $validFormats = ['csv', 'xlsx', 'json', 'txt'];
        if (!in_array($format, $validFormats)) {
            return response()->json(['message' => 'Formato inválido. Use: csv, xlsx, json, txt'], 422);
        }

        $request->merge(['per_page' => 10000]);
        $data = $this->index($request)->getData();

        $filename = 'audit_export_' . date('Y-m-d_His');

        switch ($format) {
            case 'csv':
                return $this->exportCsv($data->data, $filename);
            case 'json':
                return response()->json($data->data)
                    ->header('Content-Disposition', "attachment; filename=$filename.json");
            case 'txt':
                return $this->exportTxt($data->data, $filename);
            default:
                return response()->json(['message' => 'Formato não suportado'], 422);
        }
    }

    private function exportCsv($data, string $filename): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($data) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['ID', 'Usuário', 'Email', 'Ação', 'Modelo', 'ID Modelo', 'IP', 'Data', 'Propriedades'], ';');

            foreach ($data as $row) {
                fputcsv($handle, [
                    $row->id ?? '',
                    $row->user_name ?? '',
                    $row->user_email ?? '',
                    $row->description ?? '',
                    $row->subject_type ?? '',
                    $row->subject_id ?? '',
                    $row->ip_address ?? '',
                    $row->created_at ?? '',
                    $row->properties ?? '',
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=$filename.csv");

        return $response;
    }

    private function exportTxt($data, string $filename): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($data) {
            echo "AUDIT TRAIL EXPORT - " . date('Y-m-d H:i:s') . "\n";
            echo str_repeat("=", 80) . "\n\n";

            foreach ($data as $row) {
                echo "ID: {$row->id}\n";
                echo "Usuário: " . ($row->user_name ?? 'N/A') . " (" . ($row->user_email ?? 'N/A') . ")\n";
                echo "Ação: {$row->description}\n";
                echo "Modelo: {$row->subject_type} #{$row->subject_id}\n";
                echo "IP: {$row->ip_address}\n";
                echo "Data: {$row->created_at}\n";
                if ($row->properties) {
                    echo "Detalhes: {$row->properties}\n";
                }
                echo str_repeat("-", 40) . "\n\n";
            }
        });

        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=$filename.txt");

        return $response;
    }
}