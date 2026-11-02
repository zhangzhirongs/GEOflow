<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\TokenMismatchException;

class AdminLoginCsrf extends ValidateCsrfToken
{
    public function handle($request, Closure $next)
    {
        try {
            $response = parent::handle($request, $next);
        } catch (TokenMismatchException $exception) {
            if (! $request->routeIs('admin.login.attempt') || $request->expectsJson()) {
                throw $exception;
            }

            $response = redirect()->route('admin.login')
                ->withErrors(['session' => __('admin.login.error.session_expired')]);
        }

        if ($request->routeIs('admin.login', 'admin.login.attempt')) {
            $response->headers->set('Cache-Control', 'no-store, private, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
