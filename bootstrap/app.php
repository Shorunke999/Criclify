<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Modules\Circle\Jobs\ContributionReminderJob;
use Modules\Circle\Jobs\MarkOverDueContributionJob;

(new \Bugsnag\BugsnagLaravel\OomBootstrapper())->bootstrap();
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->job(new ContributionReminderJob)->dailyAt('08:00');
        $schedule->job(new MarkOverDueContributionJob)->dailyAt('00:05');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport([
            \Illuminate\Validation\ValidationException::class,
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Auth\Access\AuthorizationException::class,
            \Symfony\Component\HttpKernel\Exception\HttpException::class,
            \Illuminate\Database\Eloquent\ModelNotFoundException::class,
            \Illuminate\Session\TokenMismatchException::class,
        ]);

          $exceptions->report(function (Throwable $e) {
            \Bugsnag\BugsnagLaravel\Facades\Bugsnag::notifyException($e);
        });
    })->create();
