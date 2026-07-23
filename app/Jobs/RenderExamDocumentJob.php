<?php

namespace App\Jobs;

use App\Models\ExamDocument;
use App\Services\ExamDocumentRenderPipeline;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs the render pipeline out of the request/response cycle. LibreOffice
 * conversions of documents with heavy math or many pages can take well
 * beyond what's reasonable for a synchronous HTTP request, so this is
 * queued rather than run inline from the Livewire component.
 */
class RenderExamDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(private readonly int $examDocumentId)
    {
    }

    public function handle(ExamDocumentRenderPipeline $pipeline): void
    {
        $document = ExamDocument::findOrFail($this->examDocumentId);
        $document->markProcessing();

        $pipeline->render($document);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('RenderExamDocumentJob failed', [
            'exam_document_id' => $this->examDocumentId,
            'message' => $exception->getMessage(),
        ]);

        ExamDocument::find($this->examDocumentId)?->markFailed($exception->getMessage());
    }
}
