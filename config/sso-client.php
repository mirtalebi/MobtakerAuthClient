<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MobtakerSSO Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SSO authentication and user synchronization
    |
    */

    /**
     * OAuth2 Provider Configuration
     */
    'provider' => [
        'name' => env('SSO_PROVIDER_NAME', 'mobtaker-sso'),
        'client_id' => env('SSO_CLIENT_ID'),
        'client_secret' => env('SSO_CLIENT_SECRET'),
        'redirect_uri' => env('SSO_REDIRECT_URI', env('APP_URL') . '/auth/sso/callback'),
        'host' => env('SSO_HOST', 'http://localhost:8000'),
        'authorize_url' => '/oauth/authorize',
        'token_url' => '/oauth/token',
        'user_url' => '/api/user',
    ],

    /**
     * User Model Configuration
     */
    'user' => [
        'model' => env('SSO_USER_MODEL', 'App\Models\User'),
        'table' => 'users',
        'guard' => 'web',
    ],

    /**
     * SSO User Shadow Model Configuration
     */
    'sso_user' => [
        'model' => env('SSO_USER_SHADOW_MODEL', 'MobtakerSystem\SsoClient\Models\SsoUser'),
        'table' => 'sso_users',
        'enabled' => true,
    ],

    /**
     * User Synchronization Configuration
     */
    'sync' => [
        // Enable automatic user sync on login
        'enabled' => true,

        // Fields to sync from SSO server to local user table
        'user_fields' => [
            'email' => 'email',
            'name' => 'name',
            'phone' => 'phone',
            'avatar' => 'avatar',
            'mobile' => 'mobile',
        ],

        // Create user if not exists
        'create_user' => true,

        // Update user on every sync
        'update_user' => true,

        // Generate password for new users
        'generate_password' => true,

        // Event to dispatch after sync
        'dispatch_events' => true,
    ],

    /**
     * Session Configuration
     */
    'session' => [
        'sso_token_key' => 'sso_access_token',
        'sso_user_key' => 'sso_user',
        'timeout' => 3600, // 1 hour
    ],

    /**
     * Cache Configuration
     */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'prefix' => 'sso_user:',
    ],

    /**
     * Enable/Disable SSO
     */
    'enabled' => env('SSO_ENABLED', true),

    /**
     * Endpoints
     */
    'endpoints' => [
        'login' => '/auth/sso/login',
        'callback' => '/auth/sso/callback',
        'logout' => '/auth/sso/logout',
        'refresh' => '/auth/sso/refresh',
    ],

    /**
     * Scopes for OAuth2
     */
    'scopes' => ['*'],

    /**
     * Additional SSO Features
     */
    'features' => [
        'user_impersonation' => false,
        'user_sync_on_login' => true,
        'user_sync_on_middleware' => false,
        'remember_device' => false,
    ],
];
        'host' => env('SSO_HOST', 'http://localhost:8000'),
        'authorize_endpoint' => env('SSO_AUTHORIZE_ENDPOINT', '/oauth/authorize'),
        'token_endpoint' => env('SSO_TOKEN_ENDPOINT', '/oauth/token'),
        'user_endpoint' => env('SSO_USER_ENDPOINT', '/api/user'),
    ],

    /**
     * Authentication Driver Configuration
     */
    'auth' => [
        'guard' => 'sso',
        'model' => env('SSO_USER_MODEL', 'App\\Models\\User'),
        'session_name' => 'sso_auth',
        'remember_token' => true,
        'remember_duration' => 30, // days
    ],

    /**
     * User Synchronization Configuration
     */
    'sync' => [
        /**
         * Enable automatic user synchronization on login
         */
        'auto_sync' => env('SSO_AUTO_SYNC', true),

        /**
         * Enable scheduled synchronization
         */
        'scheduled' => env('SSO_SCHEDULED_SYNC', true),

        /**
         * Schedule interval for background sync (uses Laravel scheduler)
         * Options: 'hourly', 'daily', 'weekly', 'monthly', or cron expression
         */
        'schedule_interval' => env('SSO_SYNC_INTERVAL', 'hourly'),

        /**
         * Maximum number of users to sync per batch
         */
        'batch_size' => env('SSO_SYNC_BATCH_SIZE', 100),

        /**
         * Fields mapping from SSO to local user model
         * Key: SSO field, Value: Local database column
         */
        'fields_mapping' => [
            'id' => 'remote_id',
            'email' => 'email',
            'name' => 'name',
            'phone' => 'phone',
            'avatar' => 'avatar',
            'is_active' => 'is_active',
            'metadata' => 'metadata',
        ],

        /**
         * Create user if not exists during sync
         */
        'create_missing_users' => env('SSO_CREATE_MISSING_USERS', true),

        /**
         * Update existing users on sync
         */
        'update_existing_users' => env('SSO_UPDATE_EXISTING_USERS', true),

        /**
         * Deactivate users that no longer exist in SSO
         */
        'deactivate_missing_users' => env('SSO_DEACTIVATE_MISSING_USERS', false),
    ],

    /**
     * Integration Options
     */
    'integration' => [
        /**
         * Use Socialite for OAuth2 flow
         * If false, uses direct HTTP requests
         */
        'use_socialite' => env('SSO_USE_SOCIALITE', true),

        /**
         * Paths to exclude from auth middleware
         */
        'excluded_paths' => [
            '/auth/callback',
            '/auth/logout',
            '/health',
        ],
    ],

    /**
     * Token Configuration
     */
    'tokens' => [
        /**
         * Store tokens in database
         */
        'store_tokens' => env('SSO_STORE_TOKENS', true),

        /**
         * Token expiration check (minutes before expiry to refresh)
         */
        'refresh_threshold' => env('SSO_TOKEN_REFRESH_THRESHOLD', 5),
    ],

    /**
     * Logging Configuration
     */
    'logging' => [
        'enabled' => env('SSO_LOG_ENABLED', true),
        'channel' => env('SSO_LOG_CHANNEL', 'single'),
    ],
];
