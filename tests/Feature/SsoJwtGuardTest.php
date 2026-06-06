<?php

namespace MobtakerSystem\SsoClient\Tests\Feature;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use MobtakerSystem\SsoClient\Auth\Guards\SsoJwtGuard;
use MobtakerSystem\SsoClient\Tests\TestCase;

class SsoJwtGuardTest extends TestCase
{
    public function test_jwt_guard_resolves_user_from_valid_bearer_token(): void
    {
        $user = new GenericUser(['id' => 123, 'name' => 'Test User']);
        $provider = new class($user) implements UserProvider {
            private Authenticatable $user;

            public function __construct(Authenticatable $user)
            {
                $this->user = $user;
            }

            public function retrieveById($identifier): ?Authenticatable
            {
                return $this->user->getAuthIdentifier() == $identifier ? $this->user : null;
            }

            public function retrieveByToken($identifier, $token): ?Authenticatable
            {
                return null;
            }

            public function updateRememberToken(Authenticatable $user, $token): void
            {
                // no-op
            }

            public function retrieveByCredentials(array $credentials): ?Authenticatable
            {
                return null;
            }

            public function validateCredentials(Authenticatable $user, array $credentials): bool
            {
                return false;
            }

            public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
            {
                // no-op
            }
        };

        $token = $this->createJwtToken(['sub' => 123, 'exp' => time() + 3600], 'secret-key');
        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $guard = new SsoJwtGuard($provider, $request, [
            'secret' => 'secret-key',
            'algorithm' => 'HS256',
            'header' => 'Authorization',
            'prefix' => 'Bearer',
            'sub' => 'sub',
        ]);

        $this->assertTrue($guard->check());
        $this->assertSame(123, $guard->id());
        $this->assertSame($user, $guard->user());
    }

    public function test_jwt_guard_rejects_invalid_token(): void
    {
        $user = new GenericUser(['id' => 123, 'name' => 'Test User']);
        $provider = new class($user) implements UserProvider {
            private Authenticatable $user;

            public function __construct(Authenticatable $user)
            {
                $this->user = $user;
            }

            public function retrieveById($identifier): ?Authenticatable
            {
                return $this->user->getAuthIdentifier() == $identifier ? $this->user : null;
            }

            public function retrieveByToken($identifier, $token): ?Authenticatable
            {
                return null;
            }

            public function updateRememberToken(Authenticatable $user, $token): void
            {
                // no-op
            }

            public function retrieveByCredentials(array $credentials): ?Authenticatable
            {
                return null;
            }

            public function validateCredentials(Authenticatable $user, array $credentials): bool
            {
                return false;
            }

            public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
            {
                // no-op
            }
        };

        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer invalid-token']);

        $guard = new SsoJwtGuard($provider, $request, [
            'secret' => 'secret-key',
            'algorithm' => 'HS256',
            'header' => 'Authorization',
            'prefix' => 'Bearer',
            'sub' => 'sub',
        ]);

        $this->assertFalse($guard->check());
        $this->assertNull($guard->user());
    }

    private function createJwtToken(array $claims, string $secret, string $algorithm = 'HS256'): string
    {
        $header = ['alg' => $algorithm, 'typ' => 'JWT'];

        $encodedHeader = $this->base64UrlEncode(json_encode($header));
        $encodedPayload = $this->base64UrlEncode(json_encode($claims));
        $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $secret, true);
        $encodedSignature = $this->base64UrlEncode($signature);

        return implode('.', [$encodedHeader, $encodedPayload, $encodedSignature]);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
