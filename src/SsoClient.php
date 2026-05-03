<?php

namespace MobtakerSystem\SsoClient;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use MobtakerSystem\SsoClient\Events\UserAuthenticated;
use MobtakerSystem\SsoClient\Events\UserSynced;
use MobtakerSystem\SsoClient\Models\SsoUser;
use MobtakerSystem\SsoClient\Services\UserSyncService;

class SsoClient
{
    protected UserSyncService $syncService;

    public function __construct()
    {
        $this->syncService = new UserSyncService();
    }

    /**
     * Redirect to SSO login
     */
    public function redirectToLogin(): mixed
    {
        if (!config('sso-client.enabled')) {
            return null;
        }

        return Socialite::driver('mobtaker-sso')->redirect();
    }

    /**
     * Get the OAuth user from callback
     */
    public function handleCallback(): mixed
    {
        if (!config('sso-client.enabled')) {
            return null;
        }

        try {
            $socialiteUser = Socialite::driver('mobtaker-sso')->user();
            return $this->processUser($socialiteUser);
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    /**
     * Process authenticated user and sync
     */
    public function processUser($socialiteUser)
    {
        // Sync user data from SSO server
        if (config('sso-client.sync.enabled', true)) {
            $user = $this->syncService->sync($socialiteUser);

            // Store the access token in session and cache when possible
            $this->storeAccessToken($socialiteUser->token);

            event(new UserAuthenticated($user, $socialiteUser));
            event(new UserSynced($user, $socialiteUser));

            return $user;
        }

        return auth()->user();
    }

    /**
     * Get user info from SSO server
     */
    public function getUserInfo($token = null): ?array
    {
        $token = $token ?? $this->getAccessToken();

        if (!$token) {
            return null;
        }

        try {
            $response = Http::withToken($token)->get(
                config('sso-client.provider.host') . config('sso-client.provider.user_url')
            );

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    /**
     * Refresh user information
     */
    public function refreshUser(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $token = $this->getAccessToken();

        if (!$token) {
            return false;
        }

        $userInfo = $this->getUserInfo($token);

        if (!$userInfo) {
            return false;
        }

        return $this->syncService->syncFromSsoData($userInfo);
    }

    /**
     * Get stored access token from cache or session
     */
    public function getAccessToken(): ?string
    {
        if (config('sso-client.cache.enabled') && auth()->check()) {
            $token = Cache::get(
                config('sso-client.cache.prefix') . auth()->id()
            );

            if ($token) {
                return $token;
            }
        }

        return session(config('sso-client.session.sso_token_key'));
    }

    /**
     * Store access token
     */
    public function storeAccessToken(string $token): void
    {
        if (config('sso-client.cache.enabled') && auth()->check()) {
            Cache::put(
                config('sso-client.cache.prefix') . auth()->id(),
                $token,
                config('sso-client.cache.ttl')
            );
        }
        if (config('sso-client.tokens.store_tokens', true)) {
            // Store token in database if user is authenticated
            if (auth()->check()) {
                $ssoUser = SsoUser::where('user_id', auth()->id())->first();
                if ($ssoUser) {
                    $ssoUser->update(['token' => $token]);
                }
            }
        }

        session([config('sso-client.session.sso_token_key') => $token]);
    }

    /**
     * Logout from SSO
     */
    public function logout(): void
    {
        if (auth()->check()) {
            $userId = auth()->id();
            Cache::forget(config('sso-client.cache.prefix') . $userId);
        }

        session()->forget(config('sso-client.session.sso_token_key'));
        auth()->logout();
    }

    /**
     * Check if user is authenticated via SSO
     */
    public function isAuthenticated(): bool
    {
        return auth()->check() && $this->getAccessToken() !== null;
    }

    /**
     * Get sync service
     */
    public function getSyncService(): UserSyncService
    {
        return $this->syncService;
    }
}
