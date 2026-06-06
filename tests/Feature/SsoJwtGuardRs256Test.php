<?php

namespace MobtakerSystem\SsoClient\Tests\Feature;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use MobtakerSystem\SsoClient\Auth\Guards\SsoJwtGuard;
use MobtakerSystem\SsoClient\Tests\TestCase;

class SsoJwtGuardRs256Test extends TestCase
{
    public function test_rs256_guard_resolves_user_from_valid_bearer_token(): void
    {
        $user = new GenericUser(['id' => 321, 'name' => 'RSA User']);

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

        // generate RSA keypair for test
        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($res, $privateKey);
        $details = openssl_pkey_get_details($res);
        $publicKey = $details['key'];

        $token = $this->createRsJwtToken(['sub' => 321, 'exp' => time() + 3600], $privateKey, OPENSSL_ALGO_SHA256);
        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $guard = new SsoJwtGuard($provider, $request, [
            'secret' => null,
            'algorithm' => 'RS256',
            'header' => 'Authorization',
            'prefix' => 'Bearer',
            'sub' => 'sub',
            'public_key' => $publicKey,
        ]);

        $this->assertTrue($guard->check());
        $this->assertSame(321, $guard->id());
        $this->assertSame($user, $guard->user());
    }

    private function createRsJwtToken(array $claims, string $privateKey, int $opensslAlg): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];

        $encodedHeader = $this->base64UrlEncode(json_encode($header));
        $encodedPayload = $this->base64UrlEncode(json_encode($claims));
        $data = $encodedHeader . '.' . $encodedPayload;

        openssl_sign($data, $signature, $privateKey, $opensslAlg);

        $encodedSignature = $this->base64UrlEncode($signature);

        return implode('.', [$encodedHeader, $encodedPayload, $encodedSignature]);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
