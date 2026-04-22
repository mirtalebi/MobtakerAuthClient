<?php

namespace MobtakerSystem\SsoClient\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use MobtakerSystem\SsoClient\Facades\SsoClient;

class EnsureSsoAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('sso-client.enabled')) {
            return $next($request);
        }

        if (!auth()->check()) {
            return redirect(config('sso-client.endpoints.login'));
        }

        // Optionally sync user on middleware if configured
        if (config('sso-client.features.user_sync_on_middleware')) {
            SsoClient::refreshUser();
        }

        return $next($request);
    }
}
