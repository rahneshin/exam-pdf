<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:20480', // 20MB
                'mimes:docx,json',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'فقط فایل‌های Word (.docx) یا JSON مجاز هستند.',
            'file.max' => 'حجم فایل نباید بیشتر از ۲۰ مگابایت باشد.',
        ];
    }

    /**
     * Determine the logical source type from the uploaded file's extension.
     * We deliberately check the extension (validated above via mimes) rather
     * than the raw MIME type, since browsers report .docx inconsistently.
     */
    public function sourceType(): string
    {
        $extension = strtolower($this->file('file')->getClientOriginalExtension());

        return $extension === 'json' ? 'json' : 'docx';
    }
}
