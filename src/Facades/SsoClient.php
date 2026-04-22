<?php

namespace Mobtaker System\SsoClient\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mobtaker System\SsoClient\SsoClient
 */
class SsoClient extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Mobtaker System\SsoClient\SsoClient::class;
    }
}
