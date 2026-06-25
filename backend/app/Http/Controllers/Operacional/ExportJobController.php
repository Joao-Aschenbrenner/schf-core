<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacional\StoreExportJobRequest;
use App\Models\Operacional\ExportJob;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportJobController extends Controller
{
    public function index()
    {
        $jobs = ExportJob::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate();

        return response()->json($jobs);
    }

    public function store(StoreExportJobRequest $request): JsonResponse
    {
        $job = ExportJob::create(array_merge(
            $request->validated(),
            [
                'user_id' => auth()->id(),
                'status' => 'pending',
            ]
        ));

        return response()->json(['data' => $job], 201);
    }

    public function show(ExportJob $exportJob): JsonResponse
    {
        return response()->json(['data' => $exportJob]);
    }

    public function download(ExportJob $exportJob): StreamedResponse
    {
        if (!$exportJob->file_path || $exportJob->status !== 'completed') {
            abort(404, 'File not available.');
        }

        return response()->streamDownload(function () use ($exportJob) {
            readfile(storage_path('app/' . $exportJob->file_path));
        }, basename($exportJob->file_path));
    }
}
