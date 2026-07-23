<?php

use App\Http\Controllers\ExamDocumentDownloadController;
use App\Livewire\UploadExamDocument;
use Illuminate\Support\Facades\Route;

Route::get('/', UploadExamDocument::class)->name('upload');

Route::get(
    '/exam-documents/{examDocument:uuid}/download',
    ExamDocumentDownloadController::class
)->name('exam-documents.download');
