# Implementation Checklist

Use this checklist when implementing MobtakerSystem SSO Client in your Laravel application.

## Pre-Implementation

- [ ] Laravel 11.0+ or 12.0+ installed
- [ ] Database set up and accessible
- [ ] Composer available in project
- [ ] Access to MobtakerSSO admin panel

## Installation & Setup

- [ ] Run `composer require mobtaker-system/sso-client`
- [ ] Publish package configuration: `php artisan vendor:publish --tag=sso-client-config`
- [ ] Publish migrations: `php artisan vendor:publish --tag=sso-client-migrations`
- [ ] Run migrations: `php artisan migrate`

## Configuration

- [ ] Obtain OAuth2 credentials from MobtakerSSO
  - [ ] Client ID obtained
  - [ ] Client Secret obtained
  - [ ] OAuth app registered in MobtakerSSO
  
- [ ] Update `.env` file:
  ```
  - [ ] SSO_ENABLED=true
  - [ ] SSO_CLIENT_ID=...
  - [ ] SSO_CLIENT_SECRET=...
  - [ ] SSO_HOST=...
  - [ ] SSO_REDIRECT_URI=...
  ```

- [ ] Verify User model location in `config/sso-client.php`
- [ ] Review and adjust field mapping in `config/sso-client.php` under `sync.user_fields`
- [ ] Check user table has required columns:
  - [ ] `email` (indexed, unique)
  - [ ] `name`
  - [ ] `password`
  - [ ] Other fields from `user_fields` mapping

## Database Schema

- [ ] Add any custom fields to users table if needed
- [ ] Verify `sso_users` table created successfully
- [ ] Check foreign key constraints are in place
- [ ] Ensure indexes are created on `sso_id` and `user_id`

## Authentication Routes

- [ ] Create or update `routes/web.php` with login/logout routes
- [ ] Create `SsoAuthController` with:
  - [ ] `login()` method
  - [ ] `callback()` method
  - [ ] `logout()` method
  - [ ] `refresh()` method (optional)

## Views

- [ ] Update login view with SSO button:
  ```blade
  <a href="{{ route('sso.login') }}">Login with MobtakerSSO</a>
  ```
- [ ] Add feedback messages for SSO errors
- [ ] Create success redirect view (dashboard, home, etc.)

## Middleware

- [ ] Register middleware in `app/Http/Kernel.php`:
  ```php
  protected $routeMiddleware = [
      'sso' => \MobtakerSystem\SsoClient\Http\Middleware\EnsureSsoAuthenticated::class,
  ]
  ```
- [ ] Apply middleware to protected routes
- [ ] Test middleware is working correctly

## Events & Listeners (Optional)

- [ ] Create event listeners if needed:
  - [ ] UserAuthenticated listener
  - [ ] UserSynced listener
- [ ] Register listeners in `EventServiceProvider`
- [ ] Implement custom business logic in listeners

## Testing

- [ ] Test SSO login flow:
  - [ ] Click login button
  - [ ] Redirected to MobtakerSSO
  - [ ] Enter credentials
  - [ ] Redirected back to app
  - [ ] Logged in successfully

- [ ] Verify user data synced:
  - [ ] Check `users` table for new user
  - [ ] Check `sso_users` table for shadow record
  - [ ] Verify field mapping correct

- [ ] Test logout:
  - [ ] Click logout
  - [ ] Session cleared
  - [ ] Redirected to home

- [ ] Test protected routes:
  - [ ] Unauthenticated access redirects to login
  - [ ] Authenticated access allowed

- [ ] Test user refresh (if implemented):
  - [ ] Refresh endpoint returns updated user
  - [ ] User data synced correctly

## Security

- [ ] Verify `.env` is not committed to version control
- [ ] Use HTTPS in production (SSL certificate installed)
- [ ] Configure CSRF protection
- [ ] Set secure session cookies:
  ```php
  // config/session.php
  'secure' => true,
  'http_only' => true,
  'same_site' => 'lax',
  ```
- [ ] Enable rate limiting on auth routes
- [ ] Validate and sanitize input data
- [ ] Test OAuth2 callback validation

## Documentation

- [ ] Read complete `README_SSO.md`
- [ ] Read `INTEGRATION_GUIDE.md` for your use case
- [ ] Review `API_DOCUMENTATION.md` for method signatures
- [ ] Document any custom implementation

## Deployment

- [ ] All environment variables set on production
- [ ] Migrations run on production database
- [ ] Cache cleared: `php artisan cache:clear`
- [ ] Config cached: `php artisan config:cache`
- [ ] HTTPS configured and working
- [ ] Tested OAuth callback URL accessible from internet
- [ ] Monitor logs for issues

## Post-Deployment

- [ ] Monitor application logs for errors
- [ ] Check SSO user sync is working
- [ ] Verify user data accuracy
- [ ] Test user refresh periodically
- [ ] Set up automated sync if needed: `php artisan sso:sync --all`
- [ ] Review performance and caching

## Troubleshooting

If issues occur:

- [ ] Check OAuth2 credentials are correct
- [ ] Verify `SSO_HOST` is accessible
- [ ] Check redirect URI matches OAuth app
- [ ] Review application logs
- [ ] Test manually: `php artisan tinker`
  ```php
  use MobtakerSystem\SsoClient\Facades\SsoClient;
  SsoClient::isAuthenticated()
  ```
- [ ] Clear caches: `php artisan cache:clear`
- [ ] Clear config: `php artisan config:clear`
- [ ] Check database connections
- [ ] Verify user model exports fillable fields

## Optional Enhancements

- [ ] Implement token refresh mechanism
- [ ] Add user impersonation for admins
- [ ] Create admin dashboard for SSO users
- [ ] Implement role/permission sync
- [ ] Add device remember functionality
- [ ] Create user migration tool from other systems
- [ ] Implement SSO audit logging
- [ ] Add webhook support for SSO events

## Support Resources

- Documentation: `README_SSO.md`
- Integration Guide: `INTEGRATION_GUIDE.md`
- API Reference: `API_DOCUMENTATION.md`
- GitHub Issues: https://github.com/mobtaker-system/sso-client/issues
- Contact Support: support@mobtaker-system.com

---

**Last Updated**: April 22, 2026
**Package Version**: 1.0.0
