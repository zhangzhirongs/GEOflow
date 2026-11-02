<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AuthenticateAdminWeb;
use App\Models\Admin;
use App\Support\AdminWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AgentIdentityController extends Controller
{
    public function __invoke(Request $request, AuthenticateAdminWeb $authenticate): Response
    {
        return $authenticate->handle($request, fn (Request $request) => $this->identity($request));
    }

    private function identity(Request $request): Response
    {
        $secret = (string) config('geoflow.agent_sso_secret');
        abort_if(strlen($secret) < 32, 503);
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $payload = rtrim(strtr(base64_encode(json_encode([
            'iss' => 'geoflow',
            'sub' => (string) $admin->id,
            'username' => $admin->username,
            'admin' => $admin->isSuperAdmin(),
            'exp' => time() + 15,
            'target' => $request->header('X-Agent-Original-URI'),
            'method' => $request->header('X-Agent-Original-Method'),
            'csrf' => hash_hmac('sha256', $request->session()->getId(), $secret),
            'logout_url' => AdminWeb::routePath('admin.logout'),
            'logout_csrf' => $request->session()->token(),
        ], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');

        return response('', 200, [
            'Cache-Control' => 'no-store',
            'X-Agent-Identity' => $payload,
            'X-Agent-Signature' => hash_hmac('sha256', $payload, $secret),
        ]);
    }
}
