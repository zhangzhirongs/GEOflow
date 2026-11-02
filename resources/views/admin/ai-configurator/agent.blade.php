<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }} — {{ $adminSiteName }}</title>
    <link rel="stylesheet" href="{{ asset('css/agent-workspace.css') }}?v={{ filemtime(public_path('css/agent-workspace.css')) }}">
</head>
<body class="agent-workspace">
    <header class="workspace-bar">
        <a class="workspace-home" href="{{ route('admin.dashboard') }}" aria-label="{{ __('admin.ai_configurator.back_to_admin') }}">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg>
            <span>{{ $adminSiteName }}</span>
        </a>
        <span class="workspace-divider" aria-hidden="true"></span>
        <h1>{{ __('admin.ai_configurator.heading') }}</h1>
        <nav class="workspace-actions" aria-label="{{ __('admin.ai_configurator.workspace_navigation') }}">
            <a href="{{ route('admin.ai.configurator.content') }}">{{ __('admin.ai_configurator.content_title') }}</a>
            <a class="workspace-back" href="{{ route('admin.dashboard') }}">{{ __('admin.ai_configurator.back_to_admin') }}</a>
        </nav>
    </header>
    <main class="workspace-canvas">
        <iframe
            id="agent-config-frame"
            src="{{ $agentUrl }}"
            title="{{ __('admin.ai_configurator.agent_frame_title') }}"
            referrerpolicy="same-origin"
            allow="clipboard-read; clipboard-write"
        ></iframe>
    </main>
</body>
</html>
