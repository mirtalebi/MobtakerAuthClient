<?php

namespace MobtakerSystem\SsoClient\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MobtakerSystem\SsoClient\SsoClient
 */
class SsoClient extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \MobtakerSystem\SsoClient\SsoClient::class;
    }
}
