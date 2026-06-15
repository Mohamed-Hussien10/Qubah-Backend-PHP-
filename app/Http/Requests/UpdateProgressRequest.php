<?php

namespace App\Http\Requests;

use App\Enums\ProgressStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ProgressStatus::class)],
            'time_spent' => ['integer', 'min:0'],
            'score' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
