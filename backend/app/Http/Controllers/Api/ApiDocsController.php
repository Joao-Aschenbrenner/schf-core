<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ApiDocumentationService;
use Illuminate\Http\JsonResponse;

class ApiDocsController extends Controller
{
    public function __construct(
        protected ApiDocumentationService $docs
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json($this->docs->generate())
            ->header('Cache-Control', 'public, max-age=3600');
    }
}