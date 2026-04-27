<?php

namespace MobtakerSystem\SsoClient\Http\Controllers;

use Illuminate\Routing\Controller;
use MobtakerSystem\SsoClient\Facades\SsoClient;

class SsoAuthController extends Controller
{
    /**
     * Redirect user to SSO login
     */
    public function login()
    {
        return SsoClient::redirectToLogin();
    }

    /**
     * Handle SSO callback
     */
    public function callback()
    {
        $user = SsoClient::handleCallback();

        if (!$user) {
            return redirect('/login')->with('error', 'SSO authentication failed');
        }

        auth()->login($user, true);

        return redirect(config('sso-client.sync.redirect_link', '/'));
    }

    /**
     * Logout user
     */
    public function logout()
    {
        SsoClient::logout();

        return redirect('/');
    }

    /**
     * Refresh user information from SSO
     */
    public function refresh()
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $success = SsoClient::refreshUser();

        if (!$success) {
            return response()->json(['message' => 'Failed to sync user'], 500);
        }

        return response()->json(['message' => 'User synced successfully', 'user' => auth()->user()]);
    }
}
