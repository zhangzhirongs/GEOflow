<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use App\Models\AiGrowthProbe;
use Illuminate\Foundation\Http\FormRequest;

class StoreGrowthAiProbeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') instanceof Admin;
    }

    protected function prepareForValidation(): void
    {
        $providerType = trim((string) $this->input('provider_type', ''));

        $this->merge([
            'provider_type' => $providerType !== '' ? $providerType : AiGrowthProbe::CHANNEL_AUTO,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'min:4', 'max:2000'],
            'provider_type' => [
                'required',
                'string',
                'max:64',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! AiGrowthProbe::isKnownChannel((string) $value)) {
                        $fail(__('admin.analytics.overview.probe.errors.channel_unavailable'));
                    }
                },
            ],
        ];
    }
}
