# MobtakerSSO API Documentation

This document describes the API responses and data structures used by the MobtakerSystem SSO Client package.

## OAuth2 Authorization Code Flow

### 1. Authorization Request

**Endpoint**: `GET {SSO_HOST}/oauth/authorize`

**Parameters**:
```
client_id=YOUR_CLIENT_ID
redirect_uri={SSO_REDIRECT_URI}
response_type=code
state=random_state_string
scope=*
```

**Response**: Redirects user to SSO_HOST login page

### 2. Token Exchange

**Endpoint**: `POST {SSO_HOST}/oauth/token`

**Request**:
```json
{
    "grant_type": "authorization_code",
    "client_id": "YOUR_CLIENT_ID",
    "client_secret": "YOUR_CLIENT_SECRET",
    "code": "authorization_code_from_callback",
    "redirect_uri": "{SSO_REDIRECT_URI}"
}
```

**Response**:
```json
{
    "token_type": "Bearer",
    "expires_in": 3600,
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### 3. Get User Info

**Endpoint**: `GET {SSO_HOST}/api/user`

**Headers**:
```
Authorization: Bearer {access_token}
Accept: application/json
```

**Response**:
```json
{
    "id": 1,
    "email": "user@example.com",
    "name": "John Doe",
    "phone": "+1234567890",
    "mobile": "+1234567890",
    "avatar": "https://cdn.example.com/avatars/user1.jpg",
    "username": "johndoe",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-04-22T14:20:00Z"
}
```

## Package API Reference

### SsoClient Facade Methods

#### redirectToLogin()

Redirects user to SSO login page.

```php
use MobtakerSystem\SsoClient\Facades\SsoClient;

return SsoClient::redirectToLogin();
```

**Returns**: `Illuminate\Http\RedirectResponse`

#### handleCallback()

Processes OAuth2 callback and returns authenticated user.

```php
$user = SsoClient::handleCallback();
```

**Returns**: `Illuminate\Database\Eloquent\Model|null`

#### processUser($socialiteUser)

Process Socialite user and sync data.

```php
$user = SsoClient::processUser($socialiteUser);
```

**Parameters**:
- `socialiteUser`: Laravel Socialite User object

**Returns**: `Illuminate\Database\Eloquent\Model`

#### getUserInfo($token = null)

Get user information from SSO server.

```php
$userInfo = SsoClient::getUserInfo();
```

**Returns**: `array|null`

**Example Response**:
```php
[
    'id' => 1,
    'email' => 'user@example.com',
    'name' => 'John Doe',
    'phone' => '+1234567890',
    'avatar' => 'https://...',
]
```

#### refreshUser()

Refresh user information from SSO server.

```php
if (SsoClient::refreshUser()) {
    // User synced successfully
}
```

**Returns**: `bool`

#### getAccessToken()

Get stored OAuth2 access token.

```php
$token = SsoClient::getAccessToken();
```

**Returns**: `string|null`

#### storeAccessToken($token)

Store OAuth2 access token.

```php
SsoClient::storeAccessToken($token);
```

**Parameters**:
- `token`: OAuth2 access token string

**Returns**: `void`

#### logout()

Logout user and clear SSO session.

```php
SsoClient::logout();
```

**Returns**: `void`

#### isAuthenticated()

Check if user is authenticated via SSO.

```php
if (SsoClient::isAuthenticated()) {
    // User is authenticated and has valid token
}
```

**Returns**: `bool`

#### getSyncService()

Get UserSyncService instance for manual operations.

```php
$syncService = SsoClient::getSyncService();
```

**Returns**: `MobtakerSystem\SsoClient\Services\UserSyncService`

### UserSyncService Methods

#### sync($socialiteUser)

Sync user from Socialite user object.

```php
use MobtakerSystem\SsoClient\Services\UserSyncService;

$syncService = new UserSyncService();
$localUser = $syncService->sync($socialiteUser);
```

**Parameters**:
- `socialiteUser`: Laravel Socialite User object

**Returns**: `Illuminate\Database\Eloquent\Model`

#### syncFromSsoData(array $ssoUserData, ?string $token = null): bool

Sync user from SSO data array.

```php
$ssoData = [
    'id' => 1,
    'email' => 'user@example.com',
    'name' => 'John Doe',
    'phone' => '+1234567890',
];

if ($syncService->syncFromSsoData($ssoData, $token)) {
    // User synced successfully
}
```

**Returns**: `bool`

#### detachUser($userId): bool

Remove user from SSO system.

```php
$syncService->detachUser(1);
```

#### getSsoUser($userId): ?SsoUser

Get SSO user record by local user ID.

```php
$ssoUser = $syncService->getSsoUser(1);
```

**Returns**: `MobtakerSystem\SsoClient\Models\SsoUser|null`

#### getLocalUserBySsoId($ssoId)

Get local user by SSO ID.

```php
$user = $syncService->getLocalUserBySsoId(123);
```

#### existsInSso($userId): bool

Check if user exists in SSO system.

```php
if ($syncService->existsInSso(1)) {
    // User is synced with SSO
}
```

#### getSsoUserData($userId): ?array

Get SSO user data.

```php
$ssoData = $syncService->getSsoUserData(1);
```

**Example Response**:
```php
[
    'id' => 1,
    'email' => 'user@example.com',
    'name' => 'John Doe',
    'phone' => '+1234567890',
    'avatar' => 'https://...',
]
```

#### updateSyncTimestamp($userId): bool

Update user sync timestamp.

```php
$syncService->updateSyncTimestamp(1);
```

### SsoUser Model

The shadow model that links local users to SSO records.

**Attributes**:
```php
$ssoUser->id;              // Primary key
$ssoUser->user_id;         // Local user ID (foreign key)
$ssoUser->sso_id;          // SSO user ID (unique)
$ssoUser->sso_data;        // Raw SSO user data (JSON)
$ssoUser->token;           // OAuth2 access token
$ssoUser->synced_at;       // Last sync timestamp
$ssoUser->created_at;      // Creation timestamp
$ssoUser->updated_at;      // Update timestamp
```

**Methods**:
```php
// Get associated local user
$user = $ssoUser->user();

// Check if token is valid
if ($ssoUser->isTokenValid()) {
    // Token exists
}

// Check if sync is recent (within 1 hour)
if ($ssoUser->isSyncRecent()) {
    // Synced within 1 hour
}

// Update SSO data
$ssoUser->updateSsoData($newData);

// Update token
$ssoUser->updateToken($newToken);
```

## Events

### UserAuthenticated Event

Fired when user successfully authenticates via SSO.

```php
namespace MobtakerSystem\SsoClient\Events;

class UserAuthenticated
{
    public $user;              // Local authenticated user
    public $socialiteUser;     // Socialite user object
}
```

**Listener Example**:
```php
Event::listen(UserAuthenticated::class, function ($event) {
    Log::info('User authenticated', ['user_id' => $event->user->id]);
});
```

### UserSynced Event

Fired when user data is synced from SSO.

```php
namespace MobtakerSystem\SsoClient\Events;

class UserSynced
{
    public $user;              // Synced local user
    public $socialiteUser;     // Socialite user object
}
```

**Listener Example**:
```php
Event::listen(UserSynced::class, function ($event) {
    Log::info('User synced', ['user_id' => $event->user->id]);
});
```

## Middleware

### EnsureSsoAuthenticated

Ensures user is authenticated and optionally refreshes SSO data.

```php
Route::middleware(['auth', EnsureSsoAuthenticated::class])->group(function () {
    Route::get('/dashboard', 'DashboardController@index');
});
```

**Configuration**:
```php
// config/sso-client.php
'features' => [
    'user_sync_on_middleware' => false,  // Auto-refresh on middleware
]
```

## Database Schema

### sso_users Table

```sql
CREATE TABLE `sso_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `sso_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sso_data` longtext COLLATE utf8mb4_unicode_ci,
  `token` longtext COLLATE utf8mb4_unicode_ci,
  `synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sso_users_sso_id_unique` (`sso_id`),
  KEY `sso_users_user_id_index` (`user_id`),
  KEY `sso_users_sso_id_index` (`sso_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;
```

## Error Responses

### Common Errors

#### Unauthorized (401)

```json
{
    "message": "Unauthenticated"
}
```

#### Not Found (404)

```json
{
    "message": "User not found"
}
```

#### Server Error (500)

```json
{
    "message": "Failed to sync user"
}
```

## Routes

| Method | Route | Controller | Name |
|--------|-------|-----------|------|
| GET | `/auth/sso/login` | SsoAuthController@login | sso.login |
| GET | `/auth/sso/callback` | SsoAuthController@callback | sso.callback |
| POST | `/auth/sso/logout` | SsoAuthController@logout | sso.logout |
| POST | `/auth/sso/refresh` | SsoAuthController@refresh | sso.refresh |

## Rate Limiting

Recommended rate limits for SSO endpoints:

```php
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/auth/sso/login', ...);
    Route::post('/auth/sso/logout', ...);
});
```

## CORS Configuration

If API is called cross-origin:

```php
// config/cors.php
'paths' => ['api/*', '/auth/sso/*'],
'allowed_origins' => ['http://localhost:3000'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true,
```

## Version

Current API Version: **1.0.0**

## Support

For API-related questions and issues:
- GitHub: https://github.com/mobtaker-system/sso-client
- Documentation: See README_SSO.md
