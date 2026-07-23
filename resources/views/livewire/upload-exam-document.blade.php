<div class="max-w-xl mx-auto mt-10 p-6 bg-white rounded-2xl shadow-sm border border-gray-200" dir="rtl">
    <h1 class="text-xl font-bold text-gray-800 mb-1">تبدیل آزمون به PDF تصویری</h1>
    <p class="text-sm text-gray-500 mb-6">فایل Word (.docx) یا JSON سوالات را آپلود کنید.</p>

    <form wire:submit.prevent="upload" class="space-y-4">
        <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-indigo-400 transition">
            <input type="file" wire:model="file" id="file" class="hidden" accept=".docx,.json">
            <label for="file" class="cursor-pointer text-indigo-600 font-medium">
                انتخاب فایل
            </label>
            <p class="text-xs text-gray-400 mt-2">حداکثر حجم: ۲۰ مگابایت</p>

            @if ($file)
                <p class="mt-3 text-sm text-gray-700">{{ $file->getClientOriginalName() }}</p>
            @endif
        </div>

        @error('file')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror

        <div wire:loading wire:target="file" class="text-sm text-gray-500">در حال آپلود فایل...</div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="upload"
            class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium py-2.5 rounded-xl transition"
        >
            <span wire:loading.remove wire:target="upload">شروع پردازش</span>
            <span wire:loading wire:target="upload">در حال ارسال...</span>
        </button>
    </form>

    @if ($this->document)
        <div
            class="mt-6 p-4 rounded-xl border {{ $this->document->isFailed() ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-gray-50' }}"
            wire:poll.2s="$refresh"
        >
            @switch($this->document->status)
                @case('pending')
                @case('processing')
                    <div class="flex items-center gap-3">
                        <svg class="animate-spin h-5 w-5 text-indigo-600" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span class="text-sm text-gray-700">در حال تولید PDF، لطفاً صبر کنید...</span>
                    </div>
                    @break

                @case('completed')
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-green-700 font-medium">
                            آماده شد ({{ $this->document->pages_count }} صفحه)
                        </span>
                        <a
                            href="{{ route('exam-documents.download', $this->document->uuid) }}"
                            class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg"
                        >
                            دانلود PDF
                        </a>
                    </div>
                    @break

                @case('failed')
                    <p class="text-sm text-red-700">
                        پردازش ناموفق بود: {{ $this->document->error_message }}
                    </p>
                    @break
            @endswitch
        </div>
    @endif
</div>
