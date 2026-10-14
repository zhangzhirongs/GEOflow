<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use App\Models\AiGrowthProbe;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGrowthAiScheduleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin') instanceof Admin; }

    public function rules(): array
    {
        return [
            'enabled' => ['nullable', 'boolean'],
            'question' => ['required', 'string', 'min:4', 'max:2000'],
            'provider_type' => ['required', 'string', 'max:64', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! AiGrowthProbe::isKnownChannel((string) $value)) $fail(__('admin.analytics.overview.probe.errors.channel_unavailable'));
                if (! in_array((string) $value, array_column(app(\App\Services\Admin\Analytics\GrowthAiProbeService::class)->channels(), 'value'), true)) $fail(__('admin.analytics.overview.probe.errors.channel_unavailable'));
            }],
            'interval_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
        ];
    }
}
