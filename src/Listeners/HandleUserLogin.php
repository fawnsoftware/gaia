<?php

declare(strict_types=1);

namespace Arrowtide\Gaia\Listeners;

use Arrowtide\Gaia\Services\ShopifyCartService;
use Illuminate\Auth\Events\Login;
use Statamic\Statamic;

class HandleUserLogin
{
    public function __construct(
        private readonly ShopifyCartService $cart
    ) {}

    public function handle(Login $event): void
    {
        if (! Statamic::isCpRoute()) {
            $this->cart->mergeGuestCartIntoUser($event->user);
        }
    }
}
