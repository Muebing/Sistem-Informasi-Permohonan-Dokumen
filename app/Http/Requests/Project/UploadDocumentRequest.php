<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public const MAX_SIZE_KB = 5 * 1024;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'documents' => ['required', 'array', 'min:1', 'max:20'],
            'documents.*' => [
                'required',
                'file',
                'max:'.self::MAX_SIZE_KB,
                'mimes:pdf,doc,docx',
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'documents.*.mimes' => 'Each document must be a file of type: pdf, doc, docx.',
            'documents.*.max' => 'Each document may not be greater than 5 MB.',
        ];
    }
}
