<?php

namespace App\Providers;

use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Bugsnag::registerCallback(function($report){
            $report->addMetaData([
                'app' => [
                    'environment' => config('app.env'),
                    'debug' => config('app.debug'),
                    'timezone' => config('app.timezone'),
                ],
                'server' => [
                    'hostname' => gethostname(),
                    'php_version' => PHP_VERSION,
                ],
            ]);
            if(Auth::check()){
                $report->setUser([
                    'id' => Auth::id(),
                    'email' => Auth::user()->email,
                    'name' => Auth::user()->name
                ]);
            };
        });
    }
}
