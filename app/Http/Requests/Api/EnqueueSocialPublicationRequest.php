<?php

namespace App\Http\Requests\Api;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnqueueSocialPublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', 'min:1', Rule::exists('manual_publication_accounts', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:20000'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'images' => ['sometimes', 'array', 'max:20'],
            'images.*' => ['required', 'string', 'max:2048'],
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        $fieldErrors = collect($validator->errors()->messages())
            ->map(fn (array $messages): string => (string) ($messages[0] ?? 'Invalid value.'))
            ->all();

        throw new ApiException('validation_failed', '参数校验失败', 422, ['field_errors' => $fieldErrors]);
    }
}
