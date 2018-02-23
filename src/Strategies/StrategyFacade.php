<?php

namespace Ctroms\Retryable\Strategies;

use \Illuminate\Support\Facades\Facade;

class StrategyFacade extends Facade
{
    protected static function getFacadeAccessor() {
        return 'strategy';
    }
}