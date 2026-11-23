<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConsultationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $groups = $this->input('product_group');
        if (is_string($groups)) {
            $groups = array_values(array_filter(array_map('trim', explode(',', $groups))));
        }
        if ($groups !== null) {
            $this->merge(['product_group' => $groups]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:150'],
            'company' => ['sometimes', 'required', 'string', 'max:180'],
            'job_title' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => ['sometimes', 'required', 'string', 'email:rfc', 'max:190'],
            'phone' => ['sometimes', 'required', 'string', 'regex:/^\\+?[0-9][0-9\\s().-]{5,24}$/'],
            'product_group' => ['sometimes', 'required', 'array', 'min:1'],
            'product_group.*' => ['required', 'string', 'max:80', 'distinct'],
            'export_status' => ['sometimes', 'required', 'string', 'max:100'],
            'support_needed' => ['sometimes', 'required', 'string', 'max:5000'],
            'preferred_consultation_time' => ['sometimes', 'required', 'string', 'max:120'],
            'privacy_consent' => ['sometimes', 'accepted'],
        ];
    }
}
