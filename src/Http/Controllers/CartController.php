<?php

declare(strict_types=1);

namespace Arrowtide\Gaia\Http\Controllers;

use Arrowtide\Gaia\Services\ShopifyCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Statamic\Http\Controllers\Controller;

class CartController extends Controller
{
    public function __construct(
        private readonly ShopifyCartService $cart
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $cartData = $this->cart->get();

            if (! $cartData) {
                return response()->json(['error' => 'You do not have an active cart session'], 404);
            }

            return response()->json([$cartData]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve cart information'], 500);
        }
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|string',
            'quantity' => 'required|integer',
        ]);

        try {
            $this->cart->createWithItem(
                $request->input('product_id'),
                $request->input('quantity')
            );

            return response()->json(['message' => 'Cart created successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function addToCart(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|string',
            'quantity' => 'required|integer',
        ]);

        try {
            $this->cart->addLine(
                $request->input('product_id'),
                $request->input('quantity')
            );

            return response()->json(['message' => 'Product added to cart successfully']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => 'You do not have an active cart session'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateCartLine(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|string',
            'quantity' => 'required|integer',
        ]);

        try {
            $this->cart->updateLine(
                $request->input('product_id'),
                $request->input('quantity')
            );

            return response()->json(['message' => 'Product added to cart successfully']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => 'You do not have an active cart session'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function checkoutURL(Request $request): JsonResponse
    {
        try {
            $checkoutUrl = $this->cart->checkoutUrl();

            return response()->json(['checkoutUrl' => $checkoutUrl]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => 'You do not have an active cart session'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateCartDiscountCodes(Request $request): JsonResponse
    {
        $request->validate([
            'discount_codes' => 'array|max:255',
        ]);

        try {
            $this->cart->updateDiscountCodes($request->input('discount_codes'));

            return response()->json(['message' => 'Discount codes updated successfully']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => 'You do not have an active cart session'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateCartAttributes(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required|string|max:255',
        ]);

        $attributes = [
            'key' => $request->input('key'),
            'value' => $request->input('value'),
        ];

        try {
            $this->cart->updateAttributes($attributes);

            return response()->json(['message' => 'Cart Attributes updated successfully']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => 'You do not have an active cart session'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateCartNote(Request $request): JsonResponse
    {
        $request->validate([
            'note' => 'required|string|max:255',
        ]);

        try {
            $this->cart->updateNote($request->input('note'));

            return response()->json(['message' => 'Note added to cart successfully']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => 'You do not have an active cart session'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function removeFromCart(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array|max:250',
        ]);

        try {
            $this->cart->removeLines($request->input('product_ids'));

            return response()->json(['message' => 'Products removed from the to cart successfully']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => 'You do not have an active cart session'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteCart(Request $request): JsonResponse
    {
        try {
            $this->cart->forget();

            return response()->json(['message' => 'Cart has been deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateCartBuyerIdentity(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|max:250',
        ]);

        try {
            $this->cart->updateBuyerIdentity($request->input('email'));

            return response()->json(['message' => 'Cart Identity has been updated successfully']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => 'You do not have an active cart session'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
