<?php

namespace Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Repositories\Contracts\CountryRepositoryInterface;
use Modules\Core\Repositories\Contracts\CurrencyRepositoryInterface;
use Modules\Core\Repositories\Contracts\UserMetaRepositoryInterface;
use Modules\Core\Repositories\UserMetaRepository;
use Modules\Core\Repositories\Contracts\WalletRepositoryInterface;
use Modules\Core\Repositories\CountryRepository;
use Modules\Core\Repositories\WalletRepository;
use Modules\Core\Repositories\CurrencyRepository;

class CoreServiceProvider extends ServiceProvider
{
    protected string $name = 'Core';
    public function register(): void
    {
        // Register event provider manually
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->bind(UserMetaRepositoryInterface::class,UserMetaRepository::class);
        $this->app->bind(WalletRepositoryInterface::class, WalletRepository::class);
        $this->app->bind(CountryRepositoryInterface::class, CountryRepository::class);
        $this->app->bind(CurrencyRepositoryInterface::class, CurrencyRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }
}
