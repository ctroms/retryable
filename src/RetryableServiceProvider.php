<?php

namespace Ctroms\Retryable;

use Ctroms\Retryable\RetryFacade;
use Ctroms\Retryable\Sleeper;
use Ctroms\Retryable\SleeperContract;
use Ctroms\Retryable\Strategies\Strategy;
use Illuminate\Support\ServiceProvider;

class RetryableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(SleeperContract::class, Sleeper::class);
        $this->app->bind('retry', Retry::class);
        $this->app->bind('strategy', Strategy::class);
    }
}
