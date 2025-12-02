<?php

namespace App\Modules\Core\Providers;

use App\Modules\Core\Repository\CoreRepository;
use App\Modules\Core\Repository\Interface\CoreRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{

    public function register():void
    {
        $this->app->bind(CoreRepositoryInterface::class, CoreRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '../routes/api.php');
    }
}
