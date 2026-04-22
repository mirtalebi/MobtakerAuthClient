<?php

namespace MobtakerSystem\SsoClient\Providers;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class MobtakerSsoProvider extends AbstractProvider implements ProviderInterface
{
    protected $scopes = ['*'];

    protected $stateless = false;

    /**
     * Get the authentication URL for the provider.
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlWithBase(
            config('sso-client.provider.host') . config('sso-client.provider.authorize_url'),
            $state
        );
    }

    /**
     * Get the token URL for the provider.
     */
    protected function getTokenUrl(): string
    {
        return config('sso-client.provider.host') . config('sso-client.provider.token_url');
    }

    /**
     * Get the raw user for the given access token.
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get(
            config('sso-client.provider.host') . config('sso-client.provider.user_url'),
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]
        );

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Map the raw user array to a Socialite User instance.
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'] ?? null,
            'nickname' => $user['username'] ?? null,
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'avatar' => $user['avatar'] ?? null,
            'phone' => $user['phone'] ?? null,
            'mobile' => $user['mobile'] ?? null,
        ]);
    }

    /**
     * Get the access token response for the code.
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'form_params' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'redirect_uri' => $this->redirectUrl,
                'grant_type' => 'authorization_code',
            ],
        ]);

        return json_decode((string)$response->getBody(), true);
    }
}
