<?php

namespace Ctroms\Retryable;

use \Illuminate\Support\Facades\Facade;

class RetryFacade extends Facade
{
    protected static function getFacadeAccessor() {
        return 'retry';
    }
}