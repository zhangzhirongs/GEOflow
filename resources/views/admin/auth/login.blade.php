<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('admin.login.title') }} — {{ $adminSiteName }}</title>
    <script src="{{ asset('js/tailwindcss.play-cdn.js') }}"></script>
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <style>
        /* 登录页：gray-50 画布 + 白色卡片，禁用渐变与毛玻璃，与管理端 design-lock 保持一致 */
        body {
            background: var(--gf-gray-50, #f9fafb);
            color: var(--gf-gray-900, #111827);
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI",
                "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", sans-serif;
            min-height: 100vh;
        }
        .login-form {
            background: #fff;
            border: 1px solid var(--gf-gray-200, #e5e7eb);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        }
        .login-badge {
            background: var(--gf-blue-600, #2563eb);
            border-radius: 16px;
        }
        .initial-admin-hint {
            background: #fff;
            border: 1px solid var(--gf-blue-100, #dbeafe);
        }
        .login-brand {
            align-items: center;
            color: var(--gf-gray-900, #111827);
            display: flex;
            font-size: 17px;
            font-weight: 600;
            gap: 10px;
            letter-spacing: -0.02em;
        }
        .login-brand__mark {
            align-items: center;
            background: var(--gf-blue-600, #2563eb);
            border-radius: 6px;
            color: #fff;
            display: flex;
            flex: 0 0 28px;
            font-size: 14px;
            font-weight: 700;
            height: 28px;
            justify-content: center;
            width: 28px;
        }
    </style>
</head>
<body class="overflow-hidden">
<div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md px-4">
    <div class="rounded-2xl p-8 login-form">
        <div class="mb-6 flex items-center justify-between">
            <div class="login-brand">
                <span class="login-brand__mark">{{ mb_strtoupper(mb_substr(trim((string) $adminSiteName), 0, 1) ?: 'G') }}</span>
                <span class="truncate">{{ $adminSiteName }}</span>
            </div>
            <select onchange="window.location.href=this.value" class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs text-gray-600 shadow-sm">
                @foreach (\App\Support\AdminWeb::supportedLocales() as $localeCode => $localeLabel)
                    <option value="{{ route('admin.locale.switch', ['locale' => $localeCode]) }}" @selected(app()->getLocale() === $localeCode)>
                        {{ $localeLabel }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="text-center mb-8">
            <div class="login-badge mx-auto mb-4 flex h-14 w-14 items-center justify-center">
                <i data-lucide="shield-check" class="h-7 w-7 text-white"></i>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('admin.login.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('admin.login.subtitle', ['site_name' => $adminSiteName]) }}</p>
        </div>
        @if (session('message'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ session('message') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {{ $errors->first() }}
            </div>
        @endif
        @if (!empty($initialAdminHint['enabled']))
            <div id="initial-admin-hint" class="initial-admin-hint mb-6 hidden rounded-xl border border-blue-200 p-4 text-sm text-gray-700 shadow-sm" data-storage-key="{{ $initialAdminHint['storage_key'] ?? 'geoflow.initial-admin-hint' }}">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white">
                        <i data-lucide="key-round" class="h-5 w-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-gray-900">{{ __('admin.login.first_login_hint_title') }}</p>
                                <p class="mt-1 leading-6 text-gray-600">{{ __('admin.login.first_login_hint_intro') }}</p>
                            </div>
                            <button type="button" data-dismiss-initial-admin-hint class="rounded-md p-1 text-gray-400 hover:bg-white hover:text-gray-600" aria-label="{{ __('admin.login.first_login_dismiss') }}">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>
                        <div class="mt-3 grid gap-2 rounded-lg border border-blue-100 bg-white/80 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-gray-500">{{ __('admin.login.first_login_username') }}</span>
                                <code class="rounded bg-gray-100 px-2 py-1 font-mono text-xs text-gray-900">{{ $initialAdminHint['username'] ?? '' }}</code>
                            </div>
                            <div class="rounded-lg bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800">
                                {{ __('admin.login.first_login_password_from_log') }}
                            </div>
                        </div>
                        <p class="mt-3 text-xs leading-5 text-red-600">{{ __('admin.login.first_login_security') }}</p>
                    </div>
                </div>
            </div>
        @endif
        <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-6">
            @csrf
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">{{ __('admin.login.username') }}</label>
                <input type="text" id="username" name="username" required value="{{ old('username') }}"
                       class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="{{ __('admin.login.username_placeholder') }}" autocomplete="username">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">{{ __('admin.login.password') }}</label>
                <input type="password" id="password" name="password" required
                       class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="{{ __('admin.login.password_placeholder') }}" autocomplete="current-password">
            </div>
            <input type="hidden" name="remember" value="0">
            <label class="flex items-center justify-between rounded-lg border border-gray-200 bg-white/70 px-3 py-3 text-sm text-gray-600">
                <span class="flex items-center gap-2">
                    <input type="checkbox" name="remember" value="1" checked class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span>{{ __('admin.login.remember_30_days') }}</span>
                </span>
                <span class="text-xs text-gray-400">{{ __('admin.login.remember_30_days_hint') }}</span>
            </label>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg">
                {{ __('admin.login.submit') }}
            </button>
        </form>
    </div>
    <div class="text-center mt-6">
        <a href="{{ url('/') }}" class="text-gray-600 hover:text-gray-900 text-sm">{{ __('admin.login.back_home') }}</a>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const initialHint = document.getElementById('initial-admin-hint');
        if (initialHint) {
            const storageKey = initialHint.getAttribute('data-storage-key') || 'geoflow.initial-admin-hint';
            let shouldShow = true;

            try {
                shouldShow = window.localStorage.getItem(storageKey) !== 'seen';
                if (shouldShow) {
                    window.localStorage.setItem(storageKey, 'seen');
                }
            } catch (error) {
                shouldShow = true;
            }

            if (shouldShow) {
                initialHint.classList.remove('hidden');
            }

            initialHint.querySelector('[data-dismiss-initial-admin-hint]')?.addEventListener('click', function () {
                initialHint.classList.add('hidden');
                try {
                    window.localStorage.setItem(storageKey, 'seen');
                } catch (error) {}
            });
        }

        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
</body>
</html>
