<?php

namespace Mobtaker System\SsoClient\Commands;

use Illuminate\Console\Command;

class SsoClientCommand extends Command
{
    public $signature = 'sso-client';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
