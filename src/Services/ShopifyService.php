<?php

declare(strict_types=1);

namespace Arrowtide\Gaia\Services;

use Shopify\Clients\Storefront;

abstract class ShopifyService
{
    public function __construct(
        protected readonly Storefront $storefront,
    ) {}

    /**
     * Load a .graphql file from the package's resources/graphql/ directory.
     */
    protected function loadQuery(string $path): string
    {
        return file_get_contents(__DIR__ . '/../../resources/graphql/' . $path . '.graphql');
    }

    /**
     * Execute a GraphQL query against the Storefront API.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    protected function executeQuery(string $query, array $variables = []): array
    {
        $response = $this->storefront->query(data: ['query' => $query, 'variables' => $variables]);
        $decoded = json_decode($response->getBody()->getContents(), true);

        if (! empty($decoded['errors'])) {
            throw new \Exception('GraphQL query failed: ' . json_encode($decoded['errors']));
        }

        return $decoded['data'] ?? [];
    }

    /**
     * Throw if userErrors are present in a mutation response.
     *
     * @param  array<int, array{field: string, message: string}>  $userErrors
     *
     * @throws \Exception
     */
    protected function handleUserErrors(array $userErrors): void
    {
        if (! empty($userErrors)) {
            $errorMessages = array_column($userErrors, 'message');
            throw new \Exception(implode(', ', $errorMessages));
        }
    }

    /**
     * Build a Shopify GID: prefixGid('Cart', '123') -> 'gid://shopify/Cart/123'
     */
    protected function prefixGid(string $type, string $id): string
    {
        return "gid://shopify/{$type}/{$id}";
    }

    /**
     * Strip any Shopify GID prefix: 'gid://shopify/Cart/123' -> '123'
     */
    protected function stripGid(string $gid): string
    {
        return preg_replace('#^gid://shopify/[^/]+/#', '', $gid);
    }
}
