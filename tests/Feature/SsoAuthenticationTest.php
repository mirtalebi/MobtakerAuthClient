<?php

namespace MobtakerSystem\SsoClient\Tests\Feature;

use MobtakerSystem\SsoClient\Models\SsoUser;
use MobtakerSystem\SsoClient\Tests\TestCase;

class SsoAuthenticationTest extends TestCase
{
    /**
     * Test SSO login redirect
     */
    public function test_sso_login_redirect(): void
    {
        $response = $this->get(route('sso.login'));

        $response->assertRedirect();
    }

    /**
     * Test user sync on callback
     */
    public function test_user_sync_on_callback(): void
    {
        $ssoData = [
            'id' => 1,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone' => '1234567890',
        ];

        // Simulate SSO callback
        $this->postJson(route('sso.callback'), $ssoData)
            ->assertStatus(200);
    }

    /**
     * Test refresh user data
     */
    public function test_refresh_user_data(): void
    {
        $user = $this->createUser();
        $ssoUser = SsoUser::factory()
            ->for($user)
            ->create();

        $this->actingAs($user)
            ->postJson(route('sso.refresh'))
            ->assertStatus(200)
            ->assertJsonStructure(['message', 'user']);
    }

    /**
     * Test logout
     */
    public function test_logout(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->postJson(route('sso.logout'))
            ->assertStatus(302);

        $this->assertFalse(auth()->check());
    }

    /**
     * Test middleware ensures authentication
     */
    public function test_middleware_ensures_authentication(): void
    {
        $this->get(route('sso.logout'))
            ->assertStatus(302);
    }

    /**
     * Helper method to create a test user
     */
    protected function createUser()
    {
        $userModel = config('sso-client.user.model');

        return $userModel::factory()->create();
    }
}
