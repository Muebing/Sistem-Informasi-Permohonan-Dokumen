<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'status' => ['required', Rule::in(['approved', 'rejected', 'revision'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
