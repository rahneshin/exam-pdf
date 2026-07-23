<?php

namespace Tests\Feature;

use App\Jobs\RenderExamDocumentJob;
use App\Livewire\UploadExamDocument;
use App\Models\ExamDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UploadExamDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_a_docx_file_creates_a_pending_exam_document(): void
    {
        Storage::fake(config('examdocs.disk'));
        Queue::fake();

        $file = UploadedFile::fake()->create('exam.docx', 500, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        Livewire::test(UploadExamDocument::class)
            ->set('file', $file)
            ->call('upload')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('exam_documents', 1);

        $document = ExamDocument::first();
        $this->assertSame('docx', $document->source_type);
        $this->assertSame(ExamDocument::STATUS_PENDING, $document->status);

        Queue::assertPushed(RenderExamDocumentJob::class);
    }

    public function test_upload_rejects_disallowed_file_types(): void
    {
        Storage::fake(config('examdocs.disk'));

        $file = UploadedFile::fake()->create('exam.exe', 10, 'application/octet-stream');

        Livewire::test(UploadExamDocument::class)
            ->set('file', $file)
            ->call('upload')
            ->assertHasErrors(['file']);

        $this->assertDatabaseCount('exam_documents', 0);
    }
}
