<?php

declare(strict_types=1);

namespace Arrowtide\Gaia\Facades;

use Arrowtide\Gaia\Services\ShopifyCartService;
use Illuminate\Support\Facades\Facade;

class Cart extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ShopifyCartService::class;
    }
}
