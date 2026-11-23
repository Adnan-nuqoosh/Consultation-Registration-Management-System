<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $groups = $this->input('product_group');
        if (is_string($groups)) {
            $groups = array_values(array_filter(array_map('trim', explode(',', $groups))));
        }
        $this->merge(['product_group' => $groups]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'company' => ['required', 'string', 'max:180'],
            'job_title' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'phone' => [
                'required', 'string', 'regex:/^\\+?[0-9][0-9\\s().-]{5,24}$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $digits = preg_replace('/\\D+/', '', (string) $value);
                    if (strlen($digits) < 7 || strlen($digits) > 15) {
                        $fail('The phone number must contain between 7 and 15 digits.');
                    }
                },
            ],
            'product_group' => ['required', 'array', 'min:1'],
            'product_group.*' => ['required', 'string', 'max:80', 'distinct'],
            'export_status' => ['required', 'string', 'max:100'],
            'support_needed' => ['required', 'string', 'max:5000'],
            'preferred_consultation_time' => ['required', 'string', 'max:120'],
            'privacy_consent' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return ['privacy_consent.accepted' => 'You must accept the privacy policy before submitting.'];
    }
}
