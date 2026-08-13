<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use App\Models\ManualPublicationAccount;
use App\Models\ManualPublicationPersona;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveManualPublicationAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $admin = $this->user('admin');

        return $admin instanceof Admin && $admin->isSuperAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'persona_id' => [
                'required',
                'integer',
                Rule::exists((new ManualPublicationPersona)->getTable(), 'id'),
            ],
            'platform' => ['required', Rule::in(ManualPublicationAccount::PLATFORMS)],
            'publish_adapter' => [
                'nullable',
                'string',
                'max:60',
                Rule::in([
                    'zhihu_login_session',
                    'xiaohongshu_login_session',
                    'bilibili_login_session',
                    'generic_http_api',
                ]),
            ],
            'custom_platform' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->input('platform') === ManualPublicationAccount::PLATFORM_CUSTOM),
                'string',
                'max:120',
            ],
            'account_name' => ['required', 'string', 'max:160'],
            'profile_url' => ['nullable', 'url:http,https', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'auto_publish_enabled' => ['nullable', 'boolean'],
            'publish_endpoint_url' => [
                'nullable',
                'url:http,https',
                'max:1000',
                Rule::requiredIf(fn (): bool => (bool) $this->boolean('auto_publish_enabled')),
            ],
            'publish_method' => ['nullable', 'string', Rule::in(['POST', 'PUT', 'PATCH'])],
            'publish_auth_type' => ['nullable', 'string', Rule::in(['none', 'bearer', 'basic', 'header_key'])],
            'publish_login_identifier' => ['nullable', 'string', 'max:160'],
            'publish_auth_header_name' => [
                'nullable',
                'string',
                'max:120',
                Rule::requiredIf(fn (): bool => $this->input('publish_auth_type') === 'header_key'),
            ],
            'publish_basic_username' => [
                'nullable',
                'string',
                'max:160',
                Rule::requiredIf(fn (): bool => $this->input('publish_auth_type') === 'basic'),
            ],
            'publish_secret_key_id' => [
                'nullable',
                'string',
                'max:120',
                Rule::requiredIf(fn (): bool => (bool) $this->boolean('auto_publish_enabled')
                    && $this->input('platform') !== ManualPublicationAccount::PLATFORM_XIAOHONGSHU),
            ],
            'publish_secret' => [
                'nullable',
                'string',
                'max:10000',
                Rule::requiredIf(fn (): bool => (bool) $this->boolean('auto_publish_enabled')
                    && $this->input('platform') !== ManualPublicationAccount::PLATFORM_XIAOHONGSHU),
            ],
            'publish_session' => ['nullable', 'string', 'max:10000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
