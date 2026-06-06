<?php

namespace MobtakerSystem\SsoClient\Auth\Guards;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use MobtakerSystem\SsoClient\Models\SsoUser;

class SsoJwtGuard implements Guard
{
    protected Request $request;
    protected ?Authenticatable $user = null;
    protected UserProvider $provider;
    protected array $jwtConfig;
    protected ?array $claims = null;

    public function __construct(UserProvider $provider, Request $request, array $jwtConfig = [])
    {
        $this->provider = $provider;
        $this->request = $request;
        $this->jwtConfig = array_merge([
            'secret' => null,
            'algorithm' => 'HS256',
            'header' => 'Authorization',
            'prefix' => 'Bearer',
            'sub' => 'sub',
        ], $jwtConfig);
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $token = $this->getTokenFromRequest();

        if (!$token) {
            return null;
        }

        $claims = $this->decodeToken($token);

        if (!$claims || !isset($claims[$this->jwtConfig['sub']])) {
            return null;
        }

        $this->claims = $claims;
        $this->user = $this->resolveUserBySsoId($claims[$this->jwtConfig['sub']]);

        return $this->user;
    }

    public function id(): ?int
    {
        return $this->user()?->getAuthIdentifier();
    }

    public function validate(array $credentials = []): bool
    {
        $token = $credentials['token'] ?? $credentials['authorization'] ?? $credentials['Authorization'] ?? $this->getTokenFromRequest();

        if (!$token) {
            return false;
        }

        $claims = $this->decodeToken($token);

        if (!$claims || !isset($claims[$this->jwtConfig['sub']])) {
            return false;
        }

        $user = $this->resolveUserBySsoId($claims[$this->jwtConfig['sub']]);

        if (!$user) {
            return false;
        }

        $this->setUser($user);

        return true;
    }

    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    public function setUser(Authenticatable $user): self
    {
        $this->user = $user;

        return $this;
    }

    protected function getTokenFromRequest(): ?string
    {
        $header = $this->request->header($this->jwtConfig['header']);

        if (!$header) {
            return null;
        }

        $prefix = $this->jwtConfig['prefix'];

        if ($prefix && str_starts_with($header, $prefix . ' ')) {
            return trim(substr($header, strlen($prefix) + 1));
        }

        return trim($header);
    }

    protected function decodeToken(string $token): ?array
    {
        $secret = $this->jwtConfig['secret'] ?? null;
        $publicKey = $this->jwtConfig['public_key'] ?? null;
        $publicKeyPath = $this->jwtConfig['public_key_path'] ?? null;
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $header = json_decode($this->base64UrlDecode($encodedHeader), true);
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);
        $signature = $this->base64UrlDecode($encodedSignature);

        if (!is_array($header) || !is_array($payload) || $signature === false) {
            return null;
        }

        if (!$this->verifySignature($encodedHeader, $encodedPayload, $signature, $secret, $this->jwtConfig['algorithm'], $publicKey, $publicKeyPath)) {
            return null;
        }

        if (isset($payload['exp']) && time() >= $payload['exp']) {
            return null;
        }

        if (isset($payload['nbf']) && time() < $payload['nbf']) {
            return null;
        }

        return $payload;
    }

    protected function verifySignature(string $encodedHeader, string $encodedPayload, string $signature, ?string $secret, string $algorithm, ?string $publicKey = null, ?string $publicKeyPath = null): bool
    {
        $alg = strtolower($algorithm);

        // HMAC algorithms
        $hmacMap = [
            'hs256' => 'sha256',
            'hs384' => 'sha384',
            'hs512' => 'sha512',
        ];

        if (isset($hmacMap[$alg])) {
            if (empty($secret)) {
                return false;
            }

            $expected = hash_hmac($hmacMap[$alg], $encodedHeader . '.' . $encodedPayload, $secret, true);

            return hash_equals($expected, $signature);
        }

        // RSA algorithms (RS256, RS384, RS512)
        $rsMap = [
            'rs256' => OPENSSL_ALGO_SHA256,
            'rs384' => OPENSSL_ALGO_SHA384,
            'rs512' => OPENSSL_ALGO_SHA512,
        ];

        if (isset($rsMap[$alg])) {
            // public key may be provided directly or via path in config
            if (!$publicKey && $publicKeyPath) {
                $publicKey = file_get_contents(storage_path($publicKeyPath));
            }

            if (empty($publicKey)) {
                return false;
            }

            $pub = openssl_pkey_get_public($publicKey);
            if ($pub === false) {
                return false;
            }

            $data = $encodedHeader . '.' . $encodedPayload;

            $result = openssl_verify($data, $signature, $pub, $rsMap[$alg]);

            openssl_free_key($pub);

            return $result === 1;
        }

        return false;
    }

    protected function resolveUserBySsoId(mixed $ssoId): ?Authenticatable
    {
        $ssoUserModel = config('sso-client.sso_user.model', SsoUser::class);
        $ssoUser = $ssoUserModel::where('sso_id', $ssoId)->first();

        if ($ssoUser && method_exists($ssoUser, 'user')) {
            return $ssoUser->user;
        }

        // Fallback to local provider lookup when the claim is a local ID.
        if (is_scalar($ssoId)) {
            return $this->provider->retrieveById($ssoId);
        }

        return null;
    }

    protected function base64UrlDecode(string $value): string|false
    {
        $padding = 4 - (strlen($value) % 4);
        if ($padding < 4) {
            $value .= str_repeat('=', $padding);
        }

        $value = strtr($value, '-_', '+/');

        return base64_decode($value);
    }
}
