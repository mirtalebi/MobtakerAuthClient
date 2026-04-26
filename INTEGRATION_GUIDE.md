# Integration Guide: Using MobtakerSystem SSO Client

This guide shows how to integrate the MobtakerSystem SSO Client package into your Laravel applications.

## Step-by-Step Integration

### 1. Install Package

```bash
composer require mobtaker-system/sso-client
```

### 2. Publish Configuration and Migrations

```bash
# Publish configuration
php artisan vendor:publish --tag=sso-client-config

# Publish migrations
php artisan vendor:publish --tag=sso-client-migrations

# Run migrations
php artisan migrate
```

### 3. Configure Environment Variables

Add to your `.env` file:

```env
SSO_ENABLED=true
SSO_CLIENT_ID=your_client_id
SSO_CLIENT_SECRET=your_client_secret
SSO_HOST=http://localhost:8000
SSO_REDIRECT_URI=http://yourapp.local/auth/sso/callback
```

Get your OAuth2 credentials from MobtakerSSO:
1. Log in to MobtakerSSO admin panel
2. Create a new OAuth2 application
3. Set redirect URI to your app's callback URL
4. Copy Client ID and Secret to your `.env`

### 4. Update Your User Model

Ensure your `User` model is in the configured location:

```php
// config/sso-client.php
'user' => [
    'model' => 'App\Models\User',  // or your custom location
]
```

The User model should have these fields:
- `id` (primary key)
- `mobile` (unique)
- `name`
- `password`
- And any fields you configured in `sync.user_fields`

### 5. Create Authentication Controller

```php
namespace App\Http\Controllers\Auth;

use Illuminate\Routing\Controller;
use MobtakerSystem\SsoClient\Facades\SsoClient;

class SsoAuthController extends Controller
{
    /**
     * Redirect to SSO login page
     */
    public function login()
    {
        return SsoClient::redirectToLogin();
    }

    /**
     * Handle SSO callback
     */
    public function callback()
    {
        $user = SsoClient::handleCallback();

        if (!$user) {
            return redirect('/login')->with('error', 'SSO authentication failed');
        }

        auth()->login($user, true);

        return redirect('/dashboard');
    }

    /**
     * Logout from SSO
     */
    public function logout()
    {
        SsoClient::logout();

        return redirect('/');
    }
}
```

### 6. Update Routes (Optional)

If you want to customize routes, add to `routes/web.php`:

```php
use App\Http\Controllers\Auth\SsoAuthController;

Route::get('/login/sso', [SsoAuthController::class, 'login'])->name('sso.custom.login');
Route::get('/auth/sso/callback', [SsoAuthController::class, 'callback'])->name('sso.custom.callback');
Route::post('/logout', [SsoAuthController::class, 'logout'])->name('logout');
```

### 7. Update Authentication Views

In your login view template:

```blade
<div class="form-group">
    <a href="{{ route('sso.login') }}" class="btn btn-primary btn-block">
        Login with MobtakerSSO
    </a>
</div>
```

### 8. Protect Routes with SSO Middleware

For routes that require SSO authentication:

```php
use MobtakerSystem\SsoClient\Http\Middleware\EnsureSsoAuthenticated;

Route::middleware(['auth', EnsureSsoAuthenticated::class])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    });

    Route::get('/profile', function () {
        return view('profile');
    });
});
```

### 9. Listen to SSO Events

Create an event listener in `app/Listeners/`:

```php
namespace App\Listeners;

use MobtakerSystem\SsoClient\Events\UserAuthenticated;

class LogUserAuthentication
{
    public function handle(UserAuthenticated $event): void
    {
        \Log::info('User authenticated via SSO', [
            'user_id' => $event->user->id,
            'mobile' => $event->user->mobile,
            'time' => now(),
        ]);
    }
}
```

Register in `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    \MobtakerSystem\SsoClient\Events\UserAuthenticated::class => [
        \App\Listeners\LogUserAuthentication::class,
    ],
];
```

### 10. Test the Integration

1. Start your application:
   ```bash
   php artisan serve
   ```

2. Visit `http://localhost:8000/auth/sso/login`

3. You should be redirected to MobtakerSSO login

4. After authentication, you'll be redirected back and logged in

## Advanced Configuration

### Custom Field Mapping

Customize which SSO fields sync to your user table in `config/sso-client.php`:

```php
'sync' => [
    'user_fields' => [
        'mobile' => 'mobile',
        'name' => 'full_name',        // SSO 'name' → Local 'full_name'
        'phone' => 'phone_number',    // SSO 'phone' → Local 'phone_number'
        'avatar' => 'profile_photo',  // Custom mapping
    ],
]
```

Your User migration should have these fields:

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('mobile')->unique();
    $table->string('full_name');
    $table->string('phone_number')->nullable();
    $table->string('profile_photo')->nullable();
    $table->string('password')->nullable();
    $table->timestamps();
});
```

### Disable User Creation

If you want to prevent automatic user creation:

```php
// config/sso-client.php
'sync' => [
    'create_user' => false,  // Don't create new users
    'update_user' => true,   // But update existing ones
]
```

### Custom User Sync Logic

Extend the sync service for custom behavior:

```php
namespace App\Services;

use MobtakerSystem\SsoClient\Services\UserSyncService;

class CustomUserSyncService extends UserSyncService
{
    public function syncFromSsoData(array $ssoUserData, ?string $token = null): bool
    {
        // Add custom logic before sync
        $ssoUserData = $this->enrichUserData($ssoUserData);

        // Call parent sync
        $result = parent::syncFromSsoData($ssoUserData, $token);

        // Add custom logic after sync
        if ($result) {
            $this->assignDefaultRoles();
        }

        return $result;
    }

    protected function enrichUserData(array $data): array
    {
        // Add extra fields or transform data
        return $data;
    }

    protected function assignDefaultRoles(): void
    {
        // Assign roles, permissions, etc.
    }
}
```

Register in service provider:

```php
$this->app->singleton(UserSyncService::class, CustomUserSyncService::class);
```

### Refresh User Data Periodically

In a scheduled command:

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use MobtakerSystem\SsoClient\Facades\SsoClient;

class RefreshSsoUsers extends Command
{
    protected $signature = 'sso:refresh-users';

    public function handle()
    {
        foreach (auth()->user()->get() as $user) {
            auth()->setUser($user);
            SsoClient::refreshUser();
        }
    }
}
```

Schedule in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('sso:refresh-users')->hourly();
}
```

### Handle Token Expiration

Add to your middleware:

```php
namespace App\Http\Middleware;

use MobtakerSystem\SsoClient\Facades\SsoClient;

class RefreshSsoTokenIfExpired
{
    public function handle($request, $next)
    {
        if (auth()->check() && config('sso-client.sync.enabled')) {
            // Try to refresh if token seems expired
            if (!SsoClient::isAuthenticated()) {
                return redirect('/login')->with('error', 'SSO session expired');
            }
        }

        return $next($request);
    }
}
```

## Troubleshooting

### User Not Syncing

1. Verify `SSO_ENABLED=true`
2. Check configuration in `config/sso-client.php`
3. Run manual sync:
   ```bash
   php artisan sso:sync --all
   ```
4. Check logs for errors

### OAuth2 Error

1. Verify credentials in `.env`
2. Ensure redirect URI matches in OAuth2 app
3. Check SSO server is accessible
4. Verify network/firewall settings

### Session Issues

1. Check `config/session.php` is configured
2. Verify cache driver works: `php artisan cache:clear`
3. Check cookie settings in `.env`

## Security Best Practices

1. **HTTPS Only**: Use HTTPS in production
2. **Secure Credentials**: Store secrets in `.env`, never commit
3. **Token Expiration**: Implement refresh token mechanism
4. **CSRF Protection**: Ensure CSRF middleware is enabled
5. **Rate Limiting**: Protect auth endpoints from brute force
6. **Validate Data**: Always validate SSO data before use

## Support

- Documentation: See `README_SSO.md`
- Issues: Report bugs on GitHub
- Questions: Contact support

## Example Application Structure

```
app/
  Http/
    Controllers/
      Auth/
        SsoAuthController.php
    Middleware/
      EnsureSsoAuthenticated.php
  Listeners/
    LogUserAuthentication.php
  Services/
    CustomUserSyncService.php
resources/
  views/
    auth/
      login.blade.php
routes/
  web.php
config/
  sso-client.php
database/
  migrations/
    xxxx_xx_xx_create_sso_users_table.php
```
