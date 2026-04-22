# MobtakerSystem SSO Client

A comprehensive Laravel package for integrating MobtakerSSO OAuth2 authentication with automatic user synchronization and shadow table management.

## Features

- **OAuth2 Integration**: Seamless integration with MobtakerSSO provider using Laravel Socialite
- **User Synchronization**: Automatic sync of user data from SSO server to local database
- **Shadow Table**: Maintains a shadow table (`sso_users`) for SSO-related metadata
- **Token Management**: Secure storage and management of OAuth2 access tokens
- **Middleware**: Ensure SSO authentication for protected routes
- **Events**: Dispatch events for user authentication and sync operations
- **Console Commands**: Manage user synchronization via CLI
- **Caching**: Optional caching layer for performance optimization
- **Configurable**: Extensive configuration options for customization

## Installation

```bash
composer require mobtaker-system/sso-client
```

## Configuration

### Publish Configuration

```bash
php artisan vendor:publish --tag=sso-client-config
```

### Environment Variables

Add the following to your `.env` file:

```env
SSO_ENABLED=true
SSO_PROVIDER_NAME=mobtaker-sso
SSO_CLIENT_ID=your_client_id
SSO_CLIENT_SECRET=your_client_secret
SSO_HOST=http://localhost:8000
SSO_REDIRECT_URI=http://yourapp.local/auth/sso/callback
```

### Configuration File

The published configuration file (`config/sso-client.php`) contains detailed options:

```php
'provider' => [
    'client_id' => env('SSO_CLIENT_ID'),
    'client_secret' => env('SSO_CLIENT_SECRET'),
    'redirect_uri' => env('SSO_REDIRECT_URI'),
    'host' => env('SSO_HOST'),
]

'sync' => [
    'enabled' => true,
    'user_fields' => [
        'email' => 'email',
        'name' => 'name',
        'phone' => 'phone',
        'avatar' => 'avatar',
    ],
    'create_user' => true,
    'update_user' => true,
]
```

## Usage

### 1. Setup Routes

The package automatically registers routes:

- `GET /auth/sso/login` - Redirect to SSO login
- `GET /auth/sso/callback` - Handle OAuth2 callback
- `POST /auth/sso/logout` - Logout user
- `POST /auth/sso/refresh` - Refresh user data

### 2. Create Login Link

In your login view:

```blade
<a href="{{ route('sso.login') }}">
    Login with MobtakerSSO
</a>
```

### 3. Protect Routes with Middleware

```php
Route::middleware(['auth', \MobtakerSystem\SsoClient\Http\Middleware\EnsureSsoAuthenticated::class])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    });
});
```

Or in your controller:

```php
class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(\MobtakerSystem\SsoClient\Http\Middleware\EnsureSsoAuthenticated::class);
    }

    public function index()
    {
        return view('dashboard');
    }
}
```

### 4. Use the Facade

```php
use MobtakerSystem\SsoClient\Facades\SsoClient;

// Check if user is authenticated via SSO
if (SsoClient::isAuthenticated()) {
    // User is authenticated
}

// Get user info from SSO
$ssoUserInfo = SsoClient::getUserInfo();

// Refresh user data
SsoClient::refreshUser();

// Get access token
$token = SsoClient::getAccessToken();

// Logout
SsoClient::logout();
```

### 5. Console Commands

#### Sync specific user

```bash
php artisan sso:sync --user-id=1
```

#### Sync all users

```bash
php artisan sso:sync --all
```

## User Synchronization

### How It Works

1. User authenticates via MobtakerSSO
2. OAuth2 callback receives user data
3. Package syncs user data to local database
4. Creates or updates local user record
5. Creates/updates shadow record in `sso_users` table
6. Dispatches `UserAuthenticated` and `UserSynced` events

### Configuration Options

In `config/sso-client.php`:

```php
'sync' => [
    'enabled' => true,                    // Enable/disable sync
    'user_fields' => [...],               // Map SSO fields to local fields
    'create_user' => true,                // Create user if not exists
    'update_user' => true,                // Update user on sync
    'generate_password' => true,          // Generate password for new users
    'dispatch_events' => true,            // Dispatch sync events
]
```

### Field Mapping

Customize which SSO fields are synced to local user table:

```php
'user_fields' => [
    'email' => 'email',         // SSO field => Local field
    'name' => 'name',
    'phone' => 'phone',
    'avatar' => 'avatar',
    'mobile' => 'mobile',
]
```

## Events

### UserAuthenticated

Fired when user successfully authenticates:

```php
use MobtakerSystem\SsoClient\Events\UserAuthenticated;

Event::listen(UserAuthenticated::class, function ($event) {
    Log::info('User authenticated via SSO', ['user' => $event->user]);
});
```

### UserSynced

Fired when user data is synced:

```php
use MobtakerSystem\SsoClient\Events\UserSynced;

Event::listen(UserSynced::class, function ($event) {
    Log::info('User synced from SSO', ['user' => $event->user]);
});
```

## Database Schema

### sso_users Table

```sql
CREATE TABLE sso_users (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NULLABLE FOREIGN KEY,
    sso_id VARCHAR(255) UNIQUE,
    sso_data LONGTEXT NULLABLE,
    token LONGTEXT NULLABLE,
    synced_at TIMESTAMP NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Advanced Usage

### Custom User Sync Service

Extend the `UserSyncService` for custom logic:

```php
use MobtakerSystem\SsoClient\Services\UserSyncService;

class CustomUserSyncService extends UserSyncService
{
    public function syncFromSsoData(array $ssoUserData, ?string $token = null): bool
    {
        // Custom sync logic
        return parent::syncFromSsoData($ssoUserData, $token);
    }
}
```

### Token Refresh

Automatically refresh SSO user info:

```php
use MobtakerSystem\SsoClient\Facades\SsoClient;

// In middleware or scheduled task
SsoClient::refreshUser();
```

### User Impersonation (Optional)

If enabled in config:

```php
// Admin can impersonate user
Auth::login($user);
```

## Troubleshooting

### SSO Login Not Working

1. Check `.env` variables are set correctly
2. Verify `SSO_ENABLED=true`
3. Check `SSO_HOST` is accessible
4. Ensure `SSO_REDIRECT_URI` matches OAuth2 app settings

### User Not Syncing

1. Check `sync.enabled` is `true` in config
2. Review sync field mapping in config
3. Check user email field in SSO response
4. Run `php artisan sso:sync --all` for manual sync

### Token Issues

1. Verify token storage (cache or session)
2. Check cache TTL hasn't expired
3. Try refreshing user data with `SsoClient::refreshUser()`

## Security Considerations

- Store `SSO_CLIENT_SECRET` securely (use `.env`)
- Use HTTPS in production for OAuth2 callback
- Clear cached tokens on logout
- Implement token refresh mechanism
- Validate SSO data before sync
- Use middleware to enforce SSO for sensitive routes

## Publishing Assets

### Publish Configuration
```bash
php artisan vendor:publish --tag=sso-client-config
```

### Publish Migrations
```bash
php artisan vendor:publish --tag=sso-client-migrations
```

## Testing

Run tests locally:

```bash
php artisan test
```

Or with Pest:

```bash
./vendor/bin/pest
```

## Support

For issues, questions, or contributions, please visit the repository at:
https://github.com/mobtaker-system/sso-client

## License

This package is open-sourced software licensed under the MIT license.
