                    @if($account->executor_node_id)
                        <div class="mt-4 flex flex-col gap-3 rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-900 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="font-semibold">{{ __('georank.accounts.executor_box') }}</div>
                                <div class="mt-1 text-xs">{{ __('georank.session_status') }}: {{ $account->sessionStatusLabel() }}</div>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('admin.manual-publications.settings.accounts.executor-login', ['accountId' => $account->id]) }}">@csrf<button class="rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">{{ __('georank.accounts.login') }}</button></form>
                                <form method="POST" action="{{ route('admin.manual-publications.settings.accounts.executor-check', ['accountId' => $account->id]) }}">@csrf<button class="rounded-md border border-blue-200 bg-white px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">{{ __('georank.accounts.check') }}</button></form>
                            </div>
                        </div>
                    @endif
                    @if($account->platform === \App\Models\ManualPublicationAccount::PLATFORM_XIAOHONGSHU && ! $account->executor_node_id)
                        <div class="mt-4 flex flex-col gap-3 rounded-lg border border-red-100 bg-red-50 p-3 text-sm text-red-800 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="font-semibold">{{ __('georank.publish.xhs_box') }}</div>
                                <div class="mt-1 text-xs">{{ __('georank.publish.status_label') }}：{{ $profile['gateway_login_status'] ?? __('georank.publish.status_unchecked') }} @if(!empty($profile['gateway_last_checked_at'])) · {{ $profile['gateway_last_checked_at'] }} @endif</div>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('admin.manual-publications.settings.accounts.xiaohongshu-login.start', ['accountId' => $account->id]) }}">@csrf<button class="rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700">{{ __('georank.publish.login_refresh') }}</button></form>
                                <form method="POST" action="{{ route('admin.manual-publications.settings.accounts.xiaohongshu-login.status', ['accountId' => $account->id]) }}">@csrf<button class="rounded-md border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">{{ __('georank.publish.check_status') }}</button></form>
                            </div>
                        </div>
                    @endif
