<?php

namespace App\Services;

use App\Models\BlankForm;
use App\Support\UnifiedSheetLayout;
use Illuminate\Support\Facades\Storage;

class BlankSheetManifestService
{
    public function __construct(
        private UnifiedSheetLayoutService $unifiedSheetLayoutService,
    ) {
    }

    public function ensurePersisted(BlankForm $blankForm): array
    {
        $blankForm->loadMissing('test.questions.answers');
        $metadata = $blankForm->metadata ?? [];
        $layoutMetadata = $metadata['print_layout'] ?? [];
        $pageEntries = collect($layoutMetadata['pages'] ?? []);

        if (
            ($layoutMetadata['version'] ?? null) === UnifiedSheetLayout::VERSION
            && $pageEntries->isNotEmpty()
            && $pageEntries->every(fn (array $page) => !empty($page['manifest_path']) && Storage::disk('local')->exists($page['manifest_path']))
        ) {
            return $pageEntries
                ->map(fn (array $page) => $this->loadManifestFromPath((string) $page['manifest_path']))
                ->filter()
                ->values()
                ->all();
        }

        $pages = $this->unifiedSheetLayoutService->buildPagesForBlankForm($blankForm);
        $storedPages = [];

        foreach ($pages as $page) {
            $pageNumber = (int) ($page['page_number'] ?? 1);
            $manifestPath = $this->manifestPathFor($blankForm, $pageNumber);
            Storage::disk('local')->put(
                $manifestPath,
                json_encode($page, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            $storedPages[] = [
                'page_number' => $pageNumber,
                'manifest_path' => $manifestPath,
                'question_range' => $page['question_range'] ?? null,
                'qr_payload' => $page['qr_payload'] ?? null,
            ];
        }

        $metadata['print_layout'] = [
            'version' => UnifiedSheetLayout::VERSION,
            'page_count' => count($storedPages),
            'generated_at' => now()->toIso8601String(),
            'pages' => $storedPages,
        ];

        $blankForm->forceFill(['metadata' => $metadata])->save();

        return $pages;
    }

    public function buildPreview(BlankForm $blankForm): array
    {
        return $this->unifiedSheetLayoutService->buildPagesForPreview($blankForm->test, [
            'blank_form_id' => (int) $blankForm->id,
            'form_number' => (string) $blankForm->form_number,
            'student_name' => $blankForm->student_full_name,
            'group_name' => (string) ($blankForm->group_name ?? ''),
            'variant_number' => (int) ($blankForm->variant_number ?? 1),
        ]);
    }

    public function loadPageManifest(BlankForm $blankForm, int $pageNumber): ?array
    {
        $blankForm->loadMissing('test.questions.answers');
        $layoutMetadata = data_get($blankForm->metadata, 'print_layout');
        $pageEntry = collect($layoutMetadata['pages'] ?? [])
            ->first(fn (array $page) => (int) ($page['page_number'] ?? 0) === max(1, $pageNumber));

        if (!empty($pageEntry['manifest_path']) && Storage::disk('local')->exists($pageEntry['manifest_path'])) {
            return $this->loadManifestFromPath((string) $pageEntry['manifest_path']);
        }

        $pages = $this->ensurePersisted($blankForm);

        return collect($pages)
            ->first(fn (array $page) => (int) ($page['page_number'] ?? 0) === max(1, $pageNumber));
    }

    public function manifestPaths(BlankForm $blankForm): array
    {
        return collect(data_get($blankForm->metadata, 'print_layout.pages', []))
            ->pluck('manifest_path')
            ->filter()
            ->values()
            ->all();
    }

    protected function manifestPathFor(BlankForm $blankForm, int $pageNumber): string
    {
        $directory = 'blank-layouts/' . trim((string) $blankForm->form_number);

        return $directory . '/page-' . str_pad((string) $pageNumber, 3, '0', STR_PAD_LEFT) . '.json';
    }

    protected function loadManifestFromPath(string $path): ?array
    {
        if (!Storage::disk('local')->exists($path)) {
            return null;
        }

        $payload = Storage::disk('local')->get($path);

        return json_decode($payload, true);
    }
}
