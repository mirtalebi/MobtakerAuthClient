<?php

namespace MobtakerSystem\SsoClient\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserSynced
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public $user,
        public $socialiteUser,
    ) {}
}
