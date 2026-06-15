<?php

namespace App\Http\Requests;

use App\Enums\ContentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic_id' => ['required', 'integer', 'exists:topics,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(ContentType::class)],
            'file_path' => ['nullable', 'string', 'max:2048'],
            'url' => ['nullable', 'string', 'max:2048'],
            'metadata' => ['nullable', 'array'],
            'order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
