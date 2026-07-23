<?php

namespace App\Livewire;

use App\Http\Requests\StoreExamDocumentRequest;
use App\Jobs\RenderExamDocumentJob;
use App\Models\ExamDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Livewire 4 auto-registers the `pages::`/`layouts::` view namespaces only
 * when components live under the new resources/views/livewire/{pages,layouts}
 * convention. This project keeps the classic app/Livewire + resources/views/livewire
 * structure, so that namespace is never registered — without an explicit
 * #[Layout] pointing at a normal dot-path view, Livewire falls back to the
 * unregistered `layouts::app` namespace and throws "No hint path defined
 * for [layouts]". Pointing directly at components.layouts.app sidesteps
 * the namespace lookup entirely.
 */
#[Layout('components.layouts.app')]
class UploadExamDocument extends Component
{
    use WithFileUploads;

    public $file;

    public ?string $currentUuid = null;

    public function upload(): void
    {
        $this->validate(
            (new StoreExamDocumentRequest())->rules(),
            (new StoreExamDocumentRequest())->messages()
        );

        $disk = Storage::disk(config('examdocs.disk'));
        $extension = strtolower($this->file->getClientOriginalExtension());
        $sourceType = $extension === 'json' ? 'json' : 'docx';

        $randomName = Str::uuid() . '.' . $extension;
        $sourcePath = $this->file->storeAs(
            config('examdocs.paths.uploads'),
            $randomName,
            config('examdocs.disk')
        );

        $document = ExamDocument::create([
            'user_id' => auth()->id(),
            'original_filename' => $this->file->getClientOriginalName(),
            'source_type' => $sourceType,
            'source_path' => $sourcePath,
            'status' => ExamDocument::STATUS_PENDING,
        ]);

        RenderExamDocumentJob::dispatch($document->id);

        $this->currentUuid = $document->uuid;
        $this->reset('file');
    }

    #[Computed]
    public function document(): ?ExamDocument
    {
        if (! $this->currentUuid) {
            return null;
        }

        return ExamDocument::where('uuid', $this->currentUuid)->first();
    }

    public function render()
    {
        return view('livewire.upload-exam-document');
    }
}
