<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebsiteFormSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payload' => [
                'sometimes',
                'required',
                'array',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_array($value) || count($value) > 80 || strlen(json_encode($value) ?: '') > 50000) {
                        $fail('The submitted form data is invalid or too large.');
                    }
                },
            ],
        ];
    }
}
