<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebsiteFormSubmissionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! is_array($this->input('payload'))) {
            $payload = $this->except(['website', '_token']);
            $this->merge(['payload' => $payload]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'website' => ['nullable', 'string', 'max:0'],
            'payload' => [
                'required',
                'array',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_array($value) || count($value) > 80) {
                        $fail('The form payload must contain between 1 and 80 fields.');
                        return;
                    }

                    $encoded = json_encode($value);
                    if ($encoded === false || strlen($encoded) > 50000) {
                        $fail('The submitted form data is too large.');
                        return;
                    }

                    $validate = function (mixed $item, int $depth = 0) use (&$validate, $fail): void {
                        if ($depth > 4) {
                            $fail('The submitted form data is nested too deeply.');
                            return;
                        }

                        if (is_string($item) && strlen($item) > 10000) {
                            $fail('A submitted field exceeds the maximum length.');
                            return;
                        }

                        if (is_array($item)) {
                            foreach ($item as $child) {
                                $validate($child, $depth + 1);
                            }
                            return;
                        }

                        if (! is_scalar($item) && $item !== null) {
                            $fail('The submitted form data contains an unsupported value.');
                        }
                    };

                    $validate($value);
                },
            ],
        ];
    }
}
