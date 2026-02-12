<?php

declare(strict_types=1);

namespace Arrowtide\Gaia\Listeners;

use Arrowtide\Gaia\Services\ShopifyCartService;
use Illuminate\Auth\Events\Logout;
use Statamic\Statamic;

class HandleUserLogout
{
    public function __construct(
        private readonly ShopifyCartService $cart
    ) {}

    public function handle(Logout $event): void
    {
        if (! Statamic::isCpRoute()) {
            $this->cart->forget();
        }
    }
}
