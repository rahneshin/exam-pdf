<?php

namespace App\Http\Controllers;

use App\Models\ExamDocument;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamDocumentDownloadController extends Controller
{
    public function __invoke(ExamDocument $examDocument): StreamedResponse
    {
        abort_unless($examDocument->isCompleted(), Response::HTTP_NOT_FOUND);
        abort_unless($examDocument->pdf_path, Response::HTTP_NOT_FOUND);

        $disk = Storage::disk(config('examdocs.disk'));

        abort_unless($disk->exists($examDocument->pdf_path), Response::HTTP_NOT_FOUND);

        $downloadName = pathinfo($examDocument->original_filename, PATHINFO_FILENAME) . '.pdf';

        return $disk->download($examDocument->pdf_path, $downloadName);
    }
}
