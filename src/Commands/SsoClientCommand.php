<?php

namespace MobtakerSystem\SsoClient\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use MobtakerSystem\SsoClient\Models\SsoUser;
use MobtakerSystem\SsoClient\Services\UserSyncService;

class SsoClientCommand extends Command
{
    public $signature = 'sso:sync {--user-id= : Sync specific user ID} {--all : Sync all users}';

    public $description = 'Sync users from MobtakerSSO to local database';

    protected UserSyncService $syncService;

    public function __construct()
    {
        parent::__construct();
        $this->syncService = new UserSyncService();
    }

    public function handle(): int
    {
        if (!config('sso-client.enabled')) {
            $this->error('SSO client is not enabled');
            return self::FAILURE;
        }

        $userId = $this->option('user-id');
        $all = $this->option('all');

        if ($userId) {
            return $this->syncUser($userId);
        }

        if ($all) {
            return $this->syncAllUsers();
        }

        $this->info('Usage: php artisan sso:sync [--user-id=ID] [--all]');
        return self::SUCCESS;
    }

    protected function syncUser($userId): int
    {
        $ssoUser = SsoUser::find($userId);

        if (!$ssoUser) {
            $this->error("SSO user not found: {$userId}");
            return self::FAILURE;
        }

        $this->syncService->syncFromSsoData($ssoUser->sso_data, $ssoUser->token);
        $ssoUser->update(['synced_at' => now()]);

        $this->info("User {$userId} synced successfully");
        return self::SUCCESS;
    }

    protected function syncAllUsers(): int
    {
        $ssoUsers = SsoUser::all();

        if ($ssoUsers->isEmpty()) {
            $this->info('No SSO users found');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($ssoUsers->count());
        $bar->start();

        foreach ($ssoUsers as $ssoUser) {
            try {
                $this->syncService->syncFromSsoData($ssoUser->sso_data, $ssoUser->token);
                $ssoUser->update(['synced_at' => now()]);
            } catch (\Exception $e) {
                $this->warn("\nFailed to sync user {$ssoUser->id}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->info("\nAll users synced successfully");

        return self::SUCCESS;
    }
}
