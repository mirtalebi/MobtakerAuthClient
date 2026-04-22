# Quick Reference Guide

## Command Line

### Installation
```bash
composer require mobtaker-system/sso-client
php artisan vendor:publish --tag=sso-client-config
php artisan vendor:publish --tag=sso-client-migrations
php artisan migrate
```

### User Sync
```bash
# Sync specific user
php artisan sso:sync --user-id=1

# Sync all users
php artisan sso:sync --all
```

## Environment Variables

```env
# Enable/Disable
SSO_ENABLED=true

# OAuth2 Credentials
SSO_CLIENT_ID=your_client_id
SSO_CLIENT_SECRET=your_client_secret

# URLs
SSO_HOST=http://localhost:8000
SSO_REDIRECT_URI=http://yourapp.local/auth/sso/callback
```

## Facades & Methods

```php
use MobtakerSystem\SsoClient\Facades\SsoClient;

// Redirect to login
SsoClient::redirectToLogin();

// Handle callback
$user = SsoClient::handleCallback();

// Check authentication
SsoClient::isAuthenticated();

// Get access token
$token = SsoClient::getAccessToken();

// Refresh user data
SsoClient::refreshUser();

// Logout
SsoClient::logout();

// Get user info from SSO
$info = SsoClient::getUserInfo();
```

## Routes

| Route | Method | Name |
|-------|--------|------|
| `/auth/sso/login` | GET | sso.login |
| `/auth/sso/callback` | GET | sso.callback |
| `/auth/sso/logout` | POST | sso.logout |
| `/auth/sso/refresh` | POST | sso.refresh |

## Middleware

```php
use MobtakerSystem\SsoClient\Http\Middleware\EnsureSsoAuthenticated;

// Register
Route::middleware(EnsureSsoAuthenticated::class)->group(function () {
    // Protected routes
});
```

## Events

```php
use MobtakerSystem\SsoClient\Events\UserAuthenticated;
use MobtakerSystem\SsoClient\Events\UserSynced;

// Listen for authentication
Event::listen(UserAuthenticated::class, function ($event) {
    // $event->user
    // $event->socialiteUser
});

// Listen for sync
Event::listen(UserSynced::class, function ($event) {
    // $event->user
    // $event->socialiteUser
});
```

## Configuration Highlights

```php
// config/sso-client.php

// OAuth2 Provider
'provider' => [
    'client_id' => env('SSO_CLIENT_ID'),
    'client_secret' => env('SSO_CLIENT_SECRET'),
    'host' => env('SSO_HOST'),
]

// Field mapping
'sync' => [
    'user_fields' => [
        'email' => 'email',
        'name' => 'name',
        'phone' => 'phone',
    ]
]

// Enable/Disable features
'features' => [
    'user_sync_on_login' => true,
]
```

## Controller Example

```php
namespace App\Http\Controllers\Auth;

use Illuminate\Routing\Controller;
use MobtakerSystem\SsoClient\Facades\SsoClient;

class SsoAuthController extends Controller
{
    public function login()
    {
        return SsoClient::redirectToLogin();
    }

    public function callback()
    {
        $user = SsoClient::handleCallback();
        if (!$user) return redirect('/login')->with('error', 'Failed');
        auth()->login($user, true);
        return redirect('/dashboard');
    }

    public function logout()
    {
        SsoClient::logout();
        return redirect('/');
    }
}
```

## Blade Template

```blade
<!-- Login Button -->
<a href="{{ route('sso.login') }}" class="btn btn-primary">
    Login with MobtakerSSO
</a>

<!-- User Info -->
@auth
    <p>Logged in as: {{ auth()->user()->name }}</p>
    <form action="{{ route('sso.logout') }}" method="POST">
        @csrf
        <button type="submit">Logout</button>
    </form>
@endauth
```

## Database Schema (sso_users)

```sql
id              BIGINT PRIMARY KEY
user_id         BIGINT FOREIGN KEY → users.id
sso_id          VARCHAR(255) UNIQUE
sso_data        LONGTEXT (JSON)
token           LONGTEXT
synced_at       TIMESTAMP
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

## Service Methods

### SsoClient Facade
```php
SsoClient::redirectToLogin()      // RedirectResponse
SsoClient::handleCallback()        // User|null
SsoClient::processUser()           // User
SsoClient::getUserInfo()           // array|null
SsoClient::refreshUser()           // bool
SsoClient::getAccessToken()        // string|null
SsoClient::storeAccessToken()      // void
SsoClient::logout()                // void
SsoClient::isAuthenticated()       // bool
SsoClient::getSyncService()        // UserSyncService
```

### UserSyncService
```php
$service->sync($socialiteUser)              // User
$service->syncFromSsoData($data, $token)    // bool
$service->detachUser($userId)               // bool
$service->getSsoUser($userId)               // SsoUser|null
$service->getLocalUserBySsoId($ssoId)       // User|null
$service->existsInSso($userId)              // bool
$service->getSsoUserData($userId)           // array|null
$service->updateSyncTimestamp($userId)      // bool
```

## SsoUser Model

```php
$ssoUser->id;
$ssoUser->user_id;
$ssoUser->sso_id;
$ssoUser->sso_data;
$ssoUser->token;
$ssoUser->synced_at;
$ssoUser->user();                   // Relationship
$ssoUser->isTokenValid();
$ssoUser->isSyncRecent();
$ssoUser->updateSsoData($data);
$ssoUser->updateToken($token);
```

## Troubleshooting

### Login not redirecting
- Check `SSO_ENABLED=true`
- Verify credentials in `.env`
- Test SSO_HOST is accessible: `curl SSO_HOST`

### User not syncing
- Check `sync.enabled` in config
- Verify field mapping
- Run: `php artisan sso:sync --all`
- Check logs for errors

### Token issues
- Clear cache: `php artisan cache:clear`
- Check token TTL: `config('sso-client.cache.ttl')`
- Verify token storage method

### Middleware not working
- Register middleware in `Http/Kernel.php`
- Check route middleware applied
- Verify auth guard configured

## Documentation Files

| File | Purpose |
|------|---------|
| README_SSO.md | Features & overview |
| API_DOCUMENTATION.md | Detailed API reference |
| INTEGRATION_GUIDE.md | Integration steps |
| IMPLEMENTATION_CHECKLIST.md | Verification checklist |
| IMPLEMENTATION_SUMMARY.md | Complete summary |

## Common Patterns

### Protect Dashboard
```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(EnsureSsoAuthenticated::class);
});
```

### Custom Event Handler
```php
Event::listen(UserSynced::class, function ($event) {
    // Assign roles
    $event->user->assignRole('user');
    
    // Log activity
    activity()->log("User synced: {$event->user->email}");
});
```

### Refresh User Periodically
```php
// In a scheduled command
if (auth()->check()) {
    SsoClient::refreshUser();
}
```

### Check SSO Authentication
```php
if (SsoClient::isAuthenticated()) {
    // User is authenticated via SSO
}
```

## Useful Resources

- GitHub: https://github.com/mobtaker-system/sso-client
- Laravel Socialite: https://laravel.com/docs/socialite
- OAuth2 RFC: https://tools.ietf.org/html/rfc6749

---

**Last Updated**: April 22, 2026
**Version**: 1.0.0
