<?php

declare(strict_types=1);

namespace Arrowtide\Gaia\Services;

use Illuminate\Support\Facades\Log;
use Shopify\Clients\Storefront;

class ShopifyCartService extends ShopifyService
{
    public function __construct(
        Storefront $storefront,
        private readonly CartIdManager $cartIdManager,
    ) {
        parent::__construct($storefront);
    }

    // -----------------------------------------------------------------------
    // Cart ID delegation
    // -----------------------------------------------------------------------

    /**
     * Get the current cart ID.
     */
    public function getCartId(): ?string
    {
        return $this->cartIdManager->get();
    }

    /**
     * Get the cart storage key.
     */
    public function getCartKey(): string
    {
        return $this->cartIdManager->key();
    }

    // -----------------------------------------------------------------------
    // Internal: withCart helper
    // -----------------------------------------------------------------------

    /**
     * Execute a GraphQL query that requires an active cart.
     * Resolves cart ID, prefixes the GID, and merges into variables.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    protected function withCart(string $queryPath, array $variables = []): array
    {
        $cartId = $this->cartIdManager->get();
        if (! $cartId) {
            throw new \RuntimeException('No active cart session');
        }
        $variables['cartId'] = $this->prefixGid('Cart', $cartId);

        return $this->executeQuery($this->loadQuery($queryPath), $variables);
    }

    // -----------------------------------------------------------------------
    // Cart Lifecycle
    // -----------------------------------------------------------------------

    /**
     * Create an empty cart and store its ID.
     *
     * @return array<string, mixed>
     */
    public function create(): array
    {
        $query = $this->loadQuery('cart/create');

        try {
            $response = $this->executeQuery($query);
            $this->handleUserErrors($response['cartCreate']['userErrors']);

            $cartId = $this->stripGid($response['cartCreate']['cart']['id']);
            $this->cartIdManager->store($cartId, auth()->user());

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to create cart: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a cart with an initial line item and store its ID.
     *
     * @return array<string, mixed>
     */
    public function createWithItem(string $variantId, int $quantity): array
    {
        $query = $this->loadQuery('cart/create');

        $variables = [
            'cartInput' => [
                'lines' => [
                    [
                        'quantity' => $quantity,
                        'merchandiseId' => $this->prefixGid('ProductVariant', $variantId),
                    ],
                ],
            ],
        ];

        try {
            $response = $this->executeQuery($query, $variables);
            $this->handleUserErrors($response['cartCreate']['userErrors']);

            $cartId = $this->stripGid($response['cartCreate']['cart']['id']);
            $this->cartIdManager->store($cartId, auth()->user());

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to create cart: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the current cart data.
     *
     * @return array<string, mixed>|null
     */
    public function get(): ?array
    {
        $cartId = $this->cartIdManager->get();
        if (! $cartId) {
            return null;
        }

        $response = $this->executeQuery(
            $this->loadQuery('cart/get'),
            ['cartId' => $this->prefixGid('Cart', $cartId)]
        );

        if (empty($response['cart'])) {
            return null;
        }

        return $this->normalizeCart($response['cart']);
    }

    /**
     * Get raw cart data from Shopify (without normalization).
     *
     * @return array<string, mixed>
     */
    public function getRaw(string $cartId): array
    {
        return $this->executeQuery(
            $this->loadQuery('cart/get'),
            ['cartId' => $this->prefixGid('Cart', $cartId)]
        );
    }

    /**
     * Check if a cart session exists.
     */
    public function exists(): bool
    {
        return $this->cartIdManager->get() !== null;
    }

    /**
     * Forget the current cart session.
     */
    public function forget(): void
    {
        $this->cartIdManager->forget();
    }

    /**
     * Get the checkout URL for the current cart.
     */
    public function checkoutUrl(): ?string
    {
        $response = $this->withCart('cart/checkout-url');

        return $response['cart']['checkoutUrl'] ?? null;
    }

    // -----------------------------------------------------------------------
    // Line Items
    // -----------------------------------------------------------------------

    /**
     * Add a line item to the current cart.
     *
     * @return array<string, mixed>
     */
    public function addLine(string $variantId, int $quantity): array
    {
        return $this->withCart('cart/add-line', [
            'lines' => [
                'merchandiseId' => $this->prefixGid('ProductVariant', $variantId),
                'quantity' => $quantity,
            ],
        ]);
    }

    /**
     * Update a line item quantity in the current cart.
     *
     * @return array<string, mixed>
     */
    public function updateLine(string $lineId, int $quantity): array
    {
        return $this->withCart('cart/update-line', [
            'lines' => [
                'id' => $this->prefixGid('CartLine', $lineId),
                'quantity' => $quantity,
            ],
        ]);
    }

    /**
     * Remove lines from the current cart.
     *
     * @param  array<int, string>  $lineIds
     * @return array<string, mixed>
     */
    public function removeLines(array $lineIds): array
    {
        $prefixedLineIds = array_map(
            fn (string $id) => $this->prefixGid('CartLine', $id),
            $lineIds
        );

        return $this->withCart('cart/remove-lines', [
            'lineIds' => $prefixedLineIds,
        ]);
    }

    // -----------------------------------------------------------------------
    // Cart Metadata
    // -----------------------------------------------------------------------

    /**
     * Update the buyer identity (email) on the current cart.
     *
     * @return array<string, mixed>
     */
    public function updateBuyerIdentity(string $email): array
    {
        return $this->withCart('cart/update-buyer-identity', [
            'buyerIdentity' => [
                'email' => $email,
            ],
        ]);
    }

    /**
     * Update discount codes on the current cart.
     *
     * @param  array<int, string>  $codes
     * @return array<string, mixed>
     */
    public function updateDiscountCodes(array $codes): array
    {
        return $this->withCart('cart/update-discount-codes', [
            'discountCodes' => $codes,
        ]);
    }

    /**
     * Update attributes on the current cart.
     *
     * @param  array<int, array{key: string, value: string}>  $attributes
     * @return array<string, mixed>
     */
    public function updateAttributes(array $attributes): array
    {
        return $this->withCart('cart/update-attributes', [
            'attributes' => $attributes,
        ]);
    }

    /**
     * Update the note on the current cart.
     *
     * @return array<string, mixed>
     */
    public function updateNote(string $note): array
    {
        return $this->withCart('cart/update-note', [
            'note' => $note,
        ]);
    }

    // -----------------------------------------------------------------------
    // Login / Merge
    // -----------------------------------------------------------------------

    /**
     * Merge the guest cart into the authenticated user's cart on login.
     */
    public function mergeGuestCartIntoUser(mixed $user): void
    {
        $cartKey = $this->cartIdManager->key();
        $guestCartId = $this->cartIdManager->getGuestCartId();
        $userCartId = $this->cartIdManager->getUserCartId($user);

        if (! $guestCartId) {
            $this->assignCartToUser($user);

            return;
        }

        if ($userCartId) {
            $userCartData = $this->getRaw($userCartId);

            if (! empty($userCartData['cart'])) {
                $this->performMerge($userCartId, $guestCartId);
            } else {
                $user->set($cartKey, $guestCartId);
                $user->save();
            }
        } else {
            $user->set($cartKey, $guestCartId);
            $user->save();
        }

        $this->cartIdManager->forgetGuest();
        $this->assignCartToUser($user);
    }

    // -----------------------------------------------------------------------
    // Private: Response Normalization
    // -----------------------------------------------------------------------

    /**
     * Normalize a raw Shopify cart response: strip GIDs, flatten edges/nodes.
     *
     * @param  array<string, mixed>  $shopifyCart
     * @return array<string, mixed>
     */
    private function normalizeCart(array $shopifyCart): array
    {
        return [
            'id' => $this->stripGid($shopifyCart['id']),
            'checkoutUrl' => $shopifyCart['checkoutUrl'] ?? null,
            'totalQuantity' => $shopifyCart['totalQuantity'] ?? 0,
            'lines' => $this->normalizeLines($shopifyCart['lines']['edges'] ?? []),
            'cost' => $this->normalizeCost($shopifyCart['cost'] ?? []),
            'attributes' => $shopifyCart['attributes'] ?? [],
            'discount_codes' => array_column($shopifyCart['discountCodes'] ?? [], 'code'),
            'buyerIdentity' => $shopifyCart['buyerIdentity'] ?? null,
        ];
    }

    /**
     * Normalize cart line edges into a flat array with stripped GIDs.
     *
     * @param  array<int, array{node: array<string, mixed>}>  $edges
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLines(array $edges): array
    {
        return array_map(function (array $edge) {
            $node = $edge['node'];

            return array_merge(
                [
                    'id' => $this->stripGid($node['id']),
                    'variant_id' => $this->stripGid($node['merchandise']['id']),
                ],
                collect($node)->except(['id', 'merchandise'])->toArray()
            );
        }, $edges);
    }

    /**
     * Normalize cost data into a consistent structure.
     *
     * @param  array<string, mixed>  $cost
     * @return array<string, array{amount: string, currency: string}>
     */
    private function normalizeCost(array $cost): array
    {
        return [
            'subtotal' => [
                'amount' => $cost['subtotalAmount']['amount'] ?? '0',
                'currency' => $cost['subtotalAmount']['currencyCode'] ?? 'GBP',
            ],
            'total' => [
                'amount' => $cost['totalAmount']['amount'] ?? '0',
                'currency' => $cost['totalAmount']['currencyCode'] ?? 'GBP',
            ],
            'totalTax' => [
                'amount' => $cost['totalTaxAmount']['amount'] ?? '0',
                'currency' => $cost['totalTaxAmount']['currencyCode'] ?? 'GBP',
            ],
            'totalDuty' => [
                'amount' => $cost['totalDutyAmount']['amount'] ?? '0',
                'currency' => $cost['totalDutyAmount']['currencyCode'] ?? 'GBP',
            ],
        ];
    }

    /**
     * Assign a cart to the user, creating one if necessary, and update buyer identity.
     */
    private function assignCartToUser(mixed $user): void
    {
        $cartId = $this->cartIdManager->get();

        if (! $cartId) {
            $response = $this->create();
            $cartId = $this->stripGid($response['cartCreate']['cart']['id']);
            $this->cartIdManager->store($cartId, $user);
        }

        $this->executeQuery(
            $this->loadQuery('cart/update-buyer-identity'),
            [
                'cartId' => $this->prefixGid('Cart', $cartId),
                'buyerIdentity' => [
                    'email' => $user->email,
                ],
            ]
        );
    }

    /**
     * Merge guest cart items into the user's cart.
     */
    private function performMerge(string $userCartId, string $guestCartId): void
    {
        $userCart = $this->getRaw($userCartId);
        $guestCart = $this->getRaw($guestCartId);

        $userCartLines = $userCart['cart']['lines']['edges'] ?? [];
        $guestCartLines = $guestCart['cart']['lines']['edges'] ?? [];

        foreach ($guestCartLines as $guestLine) {
            $found = false;

            foreach ($userCartLines as $userLine) {
                if ($userLine['node']['merchandise']['id'] === $guestLine['node']['merchandise']['id']) {
                    $newQuantity = $userLine['node']['quantity'] + $guestLine['node']['quantity'];
                    $lineId = $this->stripGid($userLine['node']['id']);

                    $this->executeQuery(
                        $this->loadQuery('cart/update-line'),
                        [
                            'cartId' => $this->prefixGid('Cart', $userCartId),
                            'lines' => [
                                'id' => $this->prefixGid('CartLine', $lineId),
                                'quantity' => $newQuantity,
                            ],
                        ]
                    );
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                $variantId = $this->stripGid($guestLine['node']['merchandise']['id']);

                $this->executeQuery(
                    $this->loadQuery('cart/add-line'),
                    [
                        'cartId' => $this->prefixGid('Cart', $userCartId),
                        'lines' => [
                            'merchandiseId' => $this->prefixGid('ProductVariant', $variantId),
                            'quantity' => $guestLine['node']['quantity'],
                        ],
                    ]
                );
            }
        }
    }
}
