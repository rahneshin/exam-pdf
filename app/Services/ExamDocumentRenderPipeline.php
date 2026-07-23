<?php

namespace App\Services;

use App\Exceptions\DocumentRenderException;
use App\Models\ExamDocument;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Orchestrates the full pipeline for a single ExamDocument:
 *
 *   [json source] -> docx  (JsonExamToDocxConverter)
 *   docx -> pdf            (LibreOfficePdfConverter)
 *   pdf  -> png[]          (PdfToImagesConverter)
 *   png[] -> pdf (image-only) (ImagesToPdfMerger)
 *
 * All intermediate work happens in a per-job scratch directory that is
 * deleted afterwards, regardless of success or failure.
 */
class ExamDocumentRenderPipeline
{
    public function __construct(
        private readonly JsonExamToDocxConverter $jsonConverter,
        private readonly LibreOfficePdfConverter $pdfConverter,
        private readonly PdfToImagesConverter $imagesConverter,
        private readonly ImagesToPdfMerger $imagesToPdfMerger,
    ) {
    }

    public function render(ExamDocument $document): void
    {
        $disk = Storage::disk(config('examdocs.disk'));
        $workRelativeDir = config('examdocs.paths.workdir') . '/' . $document->uuid;
        $workAbsoluteDir = $disk->path($workRelativeDir);

        File::ensureDirectoryExists($workAbsoluteDir);

        try {
            $docxAbsolutePath = $this->resolveDocxSource($document, $workAbsoluteDir);

            $pdfAbsolutePath = $this->pdfConverter->convert($docxAbsolutePath, $workAbsoluteDir);

            $pngPaths = $this->imagesConverter->convert($pdfAbsolutePath, $workAbsoluteDir);

            $outputRelativeDir = config('examdocs.paths.output');
            $disk->makeDirectory($outputRelativeDir);

            $finalRelativePath = $outputRelativeDir . '/' . $document->uuid . '.pdf';
            $finalAbsolutePath = $disk->path($finalRelativePath);

            $this->imagesToPdfMerger->merge($pngPaths, $finalAbsolutePath);

            $document->markCompleted($finalRelativePath, count($pngPaths));
        } catch (Throwable $exception) {
            $document->markFailed($exception->getMessage());

            throw $exception instanceof DocumentRenderException
                ? $exception
                : DocumentRenderException::conversionFailed('pipeline', $exception->getMessage());
        } finally {
            File::deleteDirectory($workAbsoluteDir);
        }
    }

    /**
     * Returns the absolute path to a .docx file ready for LibreOffice,
     * converting from JSON first when that is the original source.
     */
    private function resolveDocxSource(ExamDocument $document, string $workAbsoluteDir): string
    {
        $disk = Storage::disk(config('examdocs.disk'));

        if ($document->source_type === 'docx') {
            return $disk->path($document->source_path);
        }

        $json = json_decode($disk->get($document->source_path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw DocumentRenderException::conversionFailed('json-parse', json_last_error_msg());
        }

        $docxAbsolutePath = $workAbsoluteDir . '/' . Str::uuid() . '.docx';
        $this->jsonConverter->convert($json, $docxAbsolutePath);

        return $docxAbsolutePath;
    }
}
