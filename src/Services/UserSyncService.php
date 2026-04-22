<?php

namespace MobtakerSystem\SsoClient\Services;

use Illuminate\Support\Str;
use MobtakerSystem\SsoClient\Models\SsoUser;

class UserSyncService
{
    /**
     * Sync user from Socialite user object
     */
    public function sync($socialiteUser)
    {
        $ssoUserData = $socialiteUser->getRaw();

        return $this->syncFromSsoData($ssoUserData, $socialiteUser->token);
    }

    /**
     * Sync user from SSO data array
     */
    public function syncFromSsoData(array $ssoUserData, ?string $token = null): bool
    {
        if (!config('sso-client.sync.enabled')) {
            return false;
        }

        $userModel = config('sso-client.user.model');
        $syncFieldsMap = config('sso-client.sync.user_fields');

        // Find or create SSO user record
        $ssoUser = SsoUser::firstOrCreate(
            ['sso_id' => $ssoUserData['id']],
            [
                'sso_data' => $ssoUserData,
                'token' => $token,
                'synced_at' => now(),
            ]
        );

        // Prepare local user data
        $localUserData = $this->mapSsoDataToLocalUser($ssoUserData, $syncFieldsMap);

        // Find local user by email
        $user = $userModel::where('email', $ssoUserData['email'] ?? null)->first();

        if ($user) {
            // Update existing user
            if (config('sso-client.sync.update_user')) {
                $user->update($localUserData);
            }
        } else {
            // Create new user if enabled
            if (config('sso-client.sync.create_user')) {
                $localUserData['password'] = config('sso-client.sync.generate_password')
                    ? bcrypt(Str::random(32))
                    : bcrypt(env('SSO_DEFAULT_PASSWORD', Str::random(32)));

                $user = $userModel::create($localUserData);
            } else {
                return false;
            }
        }

        // Link SSO user to local user
        if ($user && config('sso-client.sso_user.enabled')) {
            $ssoUser->user_id = $user->id;
            $ssoUser->save();
        }

        return true;
    }

    /**
     * Map SSO data to local user fields
     */
    protected function mapSsoDataToLocalUser(array $ssoUserData, array $fieldMap): array
    {
        $localData = [];

        foreach ($fieldMap as $ssoField => $localField) {
            if (isset($ssoUserData[$ssoField])) {
                $localData[$localField] = $ssoUserData[$ssoField];
            }
        }

        return $localData;
    }

    /**
     * Detach user from SSO
     */
    public function detachUser($userId): bool
    {
        return SsoUser::where('user_id', $userId)->delete() > 0;
    }

    /**
     * Get SSO user by local user ID
     */
    public function getSsoUser($userId): ?SsoUser
    {
        return SsoUser::where('user_id', $userId)->first();
    }

    /**
     * Get local user by SSO ID
     */
    public function getLocalUserBySsoId($ssoId)
    {
        $ssoUser = SsoUser::where('sso_id', $ssoId)->first();

        return $ssoUser ? $ssoUser->user : null;
    }

    /**
     * Check if user exists in SSO
     */
    public function existsInSso($userId): bool
    {
        return SsoUser::where('user_id', $userId)->exists();
    }

    /**
     * Get SSO user data
     */
    public function getSsoUserData($userId): ?array
    {
        $ssoUser = $this->getSsoUser($userId);

        return $ssoUser?->sso_data;
    }

    /**
     * Update sync timestamp
     */
    public function updateSyncTimestamp($userId): bool
    {
        $ssoUser = $this->getSsoUser($userId);

        if (!$ssoUser) {
            return false;
        }

        $ssoUser->synced_at = now();

        return $ssoUser->save();
    }
}
