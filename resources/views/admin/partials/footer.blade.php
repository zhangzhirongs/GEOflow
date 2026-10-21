@php
    $projectGithubUrl = 'https://github.com/zhangzhirongs/GEOflow';
    $xProfileUrl = 'https://x.com/yaojingang';
    $appVersion = (string) config('geoflow.app_version', '2.0');
    $changelogUrl = app()->getLocale() === 'en'
        ? 'https://github.com/zhangzhirongs/GEOflow/blob/main/docs/CHANGELOG_en.md'
        : 'https://github.com/zhangzhirongs/GEOflow/blob/main/docs/CHANGELOG.md';
    $helpDocsUrl = app()->getLocale() === 'en'
        ? 'https://github.com/zhangzhirongs/GEOflow/wiki/Home-English'
        : 'https://github.com/zhangzhirongs/GEOflow/wiki';
    $reverbApp = config('reverb.apps.apps.0', []);
    $reverbHost = (string) (config('reverb.servers.reverb.hostname') ?: config('app.url'));
    $reverbParsedHost = parse_url($reverbHost, PHP_URL_HOST);
    $reverbPath = trim((string) config('reverb.servers.reverb.path', ''));
    if ($reverbPath !== '' && ! str_starts_with($reverbPath, '/')) {
        $reverbPath = '/'.$reverbPath;
    }
    $reverbRuntimeConfig = [
        'enabled' => (string) config('broadcasting.default') === 'reverb',
        'key' => (string) ($reverbApp['key'] ?? ''),
        'host' => $reverbParsedHost ? (string) $reverbParsedHost : $reverbHost,
        'port' => (int) (config('reverb.apps.apps.0.options.port') ?: 443),
        'scheme' => (string) (config('reverb.apps.apps.0.options.scheme') ?: 'https'),
        'path' => rtrim($reverbPath, '/'),
        'authEndpoint' => \App\Support\AdminWeb::appPath('/broadcasting/auth'),
    ];
@endphp
<footer class="admin-footer">
    <div class="admin-footer__inner">
        <span>{{ __('admin.footer.copyright') }}</span>
        <span class="admin-footer__sep">·</span>
        <span>{{ __('admin.footer.version', ['version' => $appVersion]) }}</span>
        <span class="admin-footer__sep">·</span>
        <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <span>{{ __('admin.footer.author') }}</span>
            <a href="{{ $xProfileUrl }}" target="_blank" rel="noopener noreferrer">{{ __('admin.footer.author_x_profile') }}</a>
            <a href="{{ $projectGithubUrl }}" target="_blank" rel="noopener noreferrer">{{ __('admin.footer.project_github_link') }}</a>
            <a href="{{ $changelogUrl }}" target="_blank" rel="noopener noreferrer">{{ __('admin.footer.changelog_link') }}</a>
            <a href="{{ $helpDocsUrl }}" target="_blank" rel="noopener noreferrer">{{ __('admin.footer.help_docs_link') }}</a>
            <button type="button" data-open-admin-welcome>{{ __('admin.footer.project_intro_link') }}</button>
        </span>
    </div>
</footer>
<script>
    window.ADMIN_BASE_PATH = @json('/'.\App\Support\AdminWeb::basePath());
    window.GEOFLOW_REVERB_CONFIG = @json($reverbRuntimeConfig, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    window.adminUrl = function (path) {
        const base = window.ADMIN_BASE_PATH || '';
        if (!path) return base + '/';
        return base + '/' + String(path).replace(/^\/+/, '');
    };
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
