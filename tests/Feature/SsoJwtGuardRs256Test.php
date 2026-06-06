<?php

namespace MobtakerSystem\SsoClient\Tests\Feature;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use MobtakerSystem\SsoClient\Auth\Guards\SsoJwtGuard;
use MobtakerSystem\SsoClient\Models\SsoUser;
use MobtakerSystem\SsoClient\Tests\TestCase;

class SsoJwtGuardRs256Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('sso_users', function (Blueprint $table) {
            $table->id();
            $table->string('sso_id');
            $table->unsignedBigInteger('user_id');
            $table->json('sso_data')->nullable();
            $table->string('token')->nullable();
            $table->timestamps();
        });

        config()->set('sso-client.user.model', RsTestUser::class);
        config()->set('sso-client.sso_user.model', SsoUser::class);
    }

    public function test_rs256_guard_resolves_user_from_valid_bearer_token(): void
    {
        $user = RsTestUser::create(['name' => 'RSA User']);

        SsoUser::create([
            'sso_id' => '321',
            'user_id' => $user->id,
            'sso_data' => ['id' => '321'],
            'token' => null,
        ]);

        $provider = new class implements UserProvider {
            public function retrieveById($identifier): ?Authenticatable
            {
                return null;
            }

            public function retrieveByToken($identifier, $token): ?Authenticatable
            {
                return null;
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
        $this->assertSame($user->id, $guard->id());
        $this->assertTrue($guard->user()->is($user));
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

class RsTestUser extends Model implements Authenticatable
{
    use AuthenticatableTrait;

    protected $table = 'users';
    protected $guarded = [];
}
