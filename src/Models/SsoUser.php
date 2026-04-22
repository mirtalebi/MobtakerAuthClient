<?php

namespace MobtakerSystem\SsoClient\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SsoUser extends Model
{
    protected $table = 'sso_users';

    protected $fillable = [
        'user_id',
        'sso_id',
        'sso_data',
        'token',
        'synced_at',
    ];

    protected $casts = [
        'sso_data' => 'array',
        'synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the associated user
     */
    public function user(): BelongsTo
    {
        $userModel = config('sso-client.user.model');

        return $this->belongsTo($userModel, 'user_id');
    }

    /**
     * Check if token is expired or missing
     */
    public function isTokenValid(): bool
    {
        return $this->token !== null;
    }

    /**
     * Check if sync is recent
     */
    public function isSyncRecent(int $seconds = 3600): bool
    {
        if (!$this->synced_at) {
            return false;
        }

        return $this->synced_at->diffInSeconds(now()) < $seconds;
    }

    /**
     * Update SSO data
     */
    public function updateSsoData(array $data): bool
    {
        $this->sso_data = $data;
        $this->synced_at = now();

        return $this->save();
    }

    /**
     * Update token
     */
    public function updateToken(string $token): bool
    {
        $this->token = $token;

        return $this->save();
    }
}
