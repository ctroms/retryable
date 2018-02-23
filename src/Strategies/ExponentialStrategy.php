<?php

namespace Ctroms\Retryable\Strategies;

use Ctroms\Retryable\Strategies\BackoffStrategyContract;
use Ctroms\Retryable\Strategies\BaseStrategy;

class ExponentialStrategy extends BaseStrategy implements BackoffStrategyContract
{
    public function getSleepTime()
    {
        return $this->base * pow(2, $this->attempt);
    }
}
