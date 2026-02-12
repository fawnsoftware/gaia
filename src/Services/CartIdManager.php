<?php

declare(strict_types=1);

namespace Arrowtide\Gaia\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Statamic\Facades\Blink;
use Statamic\Facades\Site;

class CartIdManager
{
    /**
     * Get the cart storage key for the current site.
     */
    public function key(): string
    {
        $baseKey = Config::get('gaia.cart.storage_key', 'cart');
        $siteIdentifier = Site::current()->handle();

        return $baseKey . '-' . $siteIdentifier;
    }

    /**
     * Get the current cart ID from the best available source.
     * Priority: User model > Blink > Cookie.
     */
    public function get(): ?string
    {
        $key = $this->key();

        $user = auth()->user();
        if ($user) {
            return $user->get($key);
        }

        if (Blink::has($key)) {
            return Blink::get($key);
        }

        return Cookie::get($key);
    }

    /**
     * Get the guest cart ID from Blink/Cookie only (ignoring User model).
     * Used during login merge to find the pre-login guest cart.
     */
    public function getGuestCartId(): ?string
    {
        $key = $this->key();

        if (Blink::has($key)) {
            return Blink::get($key);
        }

        return Cookie::get($key);
    }

    /**
     * Get the cart ID stored on a specific user.
     */
    public function getUserCartId(mixed $user): ?string
    {
        return $user->get($this->key());
    }

    /**
     * Store a cart ID. Saves to User model if provided, otherwise Blink + Cookie for guests.
     */
    public function store(string $cartId, mixed $user = null): void
    {
        $key = $this->key();

        if ($user) {
            $user->set($key, $cartId);
            $user->save();

            return;
        }

        Blink::put($key, $cartId);
        Cookie::queue($key, $cartId, (int) Config::get('gaia.cart.cookie_duration', 14400));
    }

    /**
     * Clear guest storage only (Blink + Cookie). Leaves User model untouched.
     */
    public function forgetGuest(): void
    {
        $key = $this->key();

        Blink::forget($key);
        Cookie::queue(Cookie::forget($key));
    }

    /**
     * Clear ALL cart ID storage (User model, Blink, and Cookie).
     */
    public function forget(): void
    {
        $key = $this->key();

        $user = auth()->user();
        if ($user) {
            $user->set($key, null);
            $user->save();
        }

        Blink::forget($key);
        Cookie::queue(Cookie::forget($key));
    }
}
