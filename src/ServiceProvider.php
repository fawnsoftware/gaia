<?php

declare(strict_types=1);

namespace Arrowtide\Gaia;

use Arrowtide\Gaia\Console\InstallCommand;
use Arrowtide\Gaia\Console\ExportCommand;
use Arrowtide\Gaia\Http\Livewire\Catalog;
use Arrowtide\Gaia\Http\Livewire\CustomerOrders;
use Arrowtide\Gaia\Http\Livewire\LiveSearch;
use Arrowtide\Gaia\Http\Livewire\Minicart;
use Arrowtide\Gaia\Interfaces\CartRepositoryInterface;
use Arrowtide\Gaia\Interfaces\WishlistRepositoryInterface;
use Arrowtide\Gaia\Listeners\HandleUserLogin;
use Arrowtide\Gaia\Listeners\HandleUserLogout;
use Arrowtide\Gaia\Modifiers\PluckWithKeys;
use Arrowtide\Gaia\Modifiers\TrimShopifyId;
use Arrowtide\Gaia\Repositories\CartRepository;
use Arrowtide\Gaia\Repositories\WishlistRepository;
use Arrowtide\Gaia\Scopes\WishlistProducts;
use Arrowtide\Gaia\Tags\Gaia;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Shopify\Clients\Storefront;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $routes = [
        'web' => __DIR__.'/../routes/web.php',
        'actions' => __DIR__.'/../routes/actions.php',
    ];

    protected $tags = [
        Gaia::class,
    ];

    protected $listen = [
        Login::class => [
            HandleUserLogin::class,
        ],
        Logout::class => [
            HandleUserLogout::class,
        ],
    ];

    protected $modifiers = [
        PluckWithKeys::class,
        TrimShopifyId::class,
    ];

    protected $scopes = [
        WishlistProducts::class,
    ];

    public function bootAddon(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/gaia.php', 'gaia');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/gaia.php' => config_path('gaia.php'),
            ], 'gaia-config');
        }

        Statamic::afterInstalled(function () {
            Artisan::call('vendor:publish', [
                '--tag' => 'gaia-config',
            ]);
        });

        if (class_exists(Livewire::class)) {
            Livewire::component('catalog', Catalog::class);
            Livewire::component('customer-orders', CustomerOrders::class);
            Livewire::component('live-search', LiveSearch::class);
            Livewire::component('minicart', Minicart::class);
        }

        $this->configureCommands();
    }

    private function publishConfig(): void
    {
        Statamic::afterInstalled(function () {
            Artisan::call('vendor:publish --tag=gaia-config');
        });
    }

    public function register(): void
    {
        $this->app->bind(WishlistRepositoryInterface::class, WishlistRepository::class);
        $this->app->bind(CartRepositoryInterface::class, CartRepository::class);

        $this->app->bind(Storefront::class, function ($app) {
            return new Storefront(config('shopify.url'), config('shopify.storefront_token'));
        });
    }

    protected function configureCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
            ExportCommand::class,
        ]);
    }
}
