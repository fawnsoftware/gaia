<?php

declare(strict_types=1);

namespace Arrowtide\Gaia\Http\Livewire;

use Arrowtide\Gaia\Services\ShopifyCartService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Minicart extends Component
{
    public string $checkoutUrl = '';

    public ?string $discountCode = '';

    public array $cartDiscountCodes = [];

    public array $cartItems = [];

    public array $totals = [];

    protected array $rules = [
        'discountCode' => 'required|string|min:2',
    ];

    protected array $messages = [
        'discountCode.required' => 'Please enter a discount code.',
        'discountCode.string' => 'The discount code must be a string.',
    ];

    protected ShopifyCartService $cart;

    public function boot(ShopifyCartService $cart): void
    {
        $this->cart = $cart;
        $this->initializeCart();
    }

    #[On('update-cart')]
    public function updateCart(): void
    {
        $cartData = $this->cart->get();

        if (! $cartData) {
            $this->cartItems = ['no_results' => true];
            $this->dispatch('update-minicart-counter', count: 0);

            return;
        }

        $this->checkoutUrl = $cartData['checkoutUrl'] ?? '';
        $this->cartItems = ! empty($cartData['lines']) ? $cartData['lines'] : ['no_results' => true];
        $this->totals = $cartData['cost'] ?? [];
        $this->cartDiscountCodes = $cartData['discount_codes'] ?? [];

        if (empty($cartData['lines'])) {
            $this->dispatch('update-minicart-counter', count: 0);
        } else {
            $this->dispatch('update-minicart-counter', count: $cartData['totalQuantity']);
        }
    }

    #[On('cart-add-line')]
    public function addToCart(mixed $data): void
    {
        $this->cart->addLine($data['variantId'], $data['qty']);
        $this->updateCart();
    }

    public function removeFromCart(string $lineId): void
    {
        $this->cart->removeLines([$lineId]);
        $this->updateCart();
    }

    #[On('update-cart-item-quantity')]
    public function updateCartItemQuantity(string $lineId, int $quantity): void
    {
        $this->cart->updateLine($lineId, $quantity);
        $this->updateCart();
    }

    public function addDiscountToCart(): void
    {
        $this->validate();

        $this->discountCode = e($this->discountCode);

        if (in_array($this->discountCode, $this->cartDiscountCodes)) {
            $this->addError('discountCode', "The code '{$this->discountCode}' has already been applied.");
            $this->discountCode = '';

            return;
        }

        $discountCodes = [...$this->cartDiscountCodes, $this->discountCode];

        $response = $this->cart->updateDiscountCodes($discountCodes);
        $appliedCodes = collect(data_get($response, 'cartDiscountCodesUpdate.cart.discountCodes', []));

        $validCodes = $appliedCodes
            ->filter(fn ($code) => $code['applicable'] === true)
            ->pluck('code')
            ->all();

        $invalidCodes = $appliedCodes
            ->filter(fn ($code) => $code['applicable'] === false)
            ->pluck('code')
            ->all();

        if (in_array($this->discountCode, $invalidCodes)) {
            $this->addError('discountCode', "The code '{$this->discountCode}' is not valid.");
        }

        if (count($validCodes) !== count($discountCodes)) {
            $this->cart->updateDiscountCodes($validCodes);
        }

        $this->cartDiscountCodes = $validCodes;
        $this->updateCart();
        $this->discountCode = '';
    }

    public function removeDiscountFromCart(string $discountCode): void
    {
        if (empty($discountCode)) {
            throw new \InvalidArgumentException('Discount code is required.');
        }

        $index = array_search($discountCode, $this->cartDiscountCodes);

        if ($index !== false) {
            unset($this->cartDiscountCodes[$index]);
            $this->cartDiscountCodes = array_values($this->cartDiscountCodes);

            $this->cart->updateDiscountCodes($this->cartDiscountCodes);
            $this->updateCart();
        }
    }

    protected function initializeCart(): void
    {
        $this->updateCart();
    }

    public function render(): View
    {
        return view('features/minicart/minicart', [
            'lines' => $this->cartItems,
            'totals' => $this->totals,
            'discount_codes' => $this->cartDiscountCodes,
            'cart_empty' => array_key_exists('no_results', $this->cartItems) && $this->cartItems['no_results'] == true,
        ]);
    }
}
