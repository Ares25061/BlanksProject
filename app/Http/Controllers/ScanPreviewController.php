<?php

namespace App\Http\Controllers;

use App\Services\ScanPreviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ScanPreviewController extends Controller
{
    public function __construct(
        private ScanPreviewService $scanPreviewService,
    ) {
    }

    public function show(Request $request, string $token)
    {
        $preview = $this->scanPreviewService->getPreview($token, (int) $request->user()->id);
        $data = $preview['data'] ?? [];
        $data['preview_token'] = $token;

        return response()->json([
            'status' => $preview['status'] ?? 'success',
            'data' => $data,
            'grade' => $preview['grade'] ?? null,
        ]);
    }

    public function scanImage(Request $request, string $token)
    {
        $preview = $this->scanPreviewService->getPreview($token, (int) $request->user()->id);
        $pagePath = $this->scanPreviewService->resolvePreviewScanPath($preview, (int) $request->query('page', 1));

        if (!$pagePath || !Storage::disk('local')->exists($pagePath)) {
            throw ValidationException::withMessages([
                'preview' => 'Изображение скана для временного результата не найдено.',
            ]);
        }

        return Storage::disk('local')->response(
            $pagePath,
            basename($pagePath),
            [],
            'inline'
        );
    }
}
