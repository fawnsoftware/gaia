<?php

declare(strict_types=1);

namespace Arrowtide\Gaia\Http\Middleware;

use Arrowtide\Gaia\Services\ShopifyCartService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CartManagement
{
    public function __construct(
        private readonly ShopifyCartService $cart
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->cart->exists()) {
            $cartData = $this->cart->get();

            if ($cartData) {
                return $next($request);
            }
        }

        return $this->createCartAndProceed($request, $next);
    }

    /**
     * Create an empty cart and proceed with the request.
     */
    private function createCartAndProceed(Request $request, Closure $next): mixed
    {
        try {
            $this->cart->create();

            return $next($request);
        } catch (\Exception $e) {
            Log::error('Unable to create a cart: ' . $e->getMessage());

            return response()->json(['error' => 'Unable to create a cart at this time.'], 500);
        }
    }
}
