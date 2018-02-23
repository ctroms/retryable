<?php

namespace Ctroms\Retryable\Strategies;

use Ctroms\Retryable\Strategies\BackoffStrategyContract;
use Ctroms\Retryable\Strategies\BaseStrategy;

class LinearStrategy extends BaseStrategy implements BackoffStrategyContract
{
    public function getSleepTime()
    {
        return $this->base * $this->attempt;
    }
}
