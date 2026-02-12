<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Playground
    |--------------------------------------------------------------------------
    |
    | The playground is a place where you can test out various features and
    | see a complete overview of components and their styles.
    |
    | enabled: 'auto' to enable the playground only when APP_ENV=local
    |           true to always enable the playground
    |           false to disable the playground
    |
    | route: The url of the playground
    |
    */

    'playground' => [
        'enabled' => 'auto', // 'auto' | true | false
        'route' => 'playground',
    ],

    /*
    |--------------------------------------------------------------------------
    | Variant Picker
    |--------------------------------------------------------------------------
    |
    | In order to use the custom pickers available in Gaia, you must
    | first register them here. You can also link the variant to
    | metadata fields, which will then be available inside the
    | options tag.
    |
    | // https://gaiakit.com/docs/product-detail#variant-picker
    |
    */

    'variant_picker' => [
        'default' => 'button',

        'custom' => [
            // ...
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    |
    | Configure the filters used on product listings.
    |
    | https://gaiakit.com/docs/product-detail#filtering
    |
    */

    'filtering' => [

        'cache_enabled' => true,
        'cache_duration' => 43200,

        'filters' => [
            // ...
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Currency
    |--------------------------------------------------------------------------
    |
    | This setting defines the default currency code to be used when no specific
    | currency is provided or when the application's locale does not have an
    | associated currency. It ensures that your application has a fallback currency
    | for cases where currency information might be missing or unspecified.
    |
    | Example:
    |   'USD' // US Dollar is used as the default fallback currency
    |
    | You can change this value to any valid ISO 4217 currency code based on
    | your application's needs or the primary currency used in Shopify.
    |
    */

    'default_currency' => 'USD',

    /*
    |--------------------------------------------------------------------------
    | Cart
    |--------------------------------------------------------------------------
    |
    | Configure the cart storage key and cookie duration.
    |
    | storage_key: The base key used for storing the cart ID.
    | cookie_duration: How long the guest cart cookie persists (in minutes).
    |
    */

    'cart' => [
        'storage_key' => 'cart',
        'cookie_duration' => 14400, // 10 days in minutes
    ],

    'search' => [
        /*
        |--------------------------------------------------------------------------
        | Minimum Characters
        |--------------------------------------------------------------------------
        |
        | The minimum number of characters required to start the search.
        |
        */

        'min_characters' => 3,
    ],

    'wishlists' => [

        /*
        |--------------------------------------------------------------------------
        | Wishlists Enabled
        |--------------------------------------------------------------------------
        |
        | 'true' or 'false'. Choose 'false' if you don't want the ability for
        | customers to add things to a wishlist.
        |
        */

        'enabled' => true,

        /*
        |--------------------------------------------------------------------------
        | Maximum Wishlist Collections
        |--------------------------------------------------------------------------
        |
        | The maximum number of collections a user can create.
        |
        */

        'max_collections' => 5,
    ],
];
