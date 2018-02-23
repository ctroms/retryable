<?php

namespace Ctroms\Retryable\Strategies;

use Ctroms\Retryable\Strategies\BackoffStrategyContract;

class FullJitter implements BackoffStrategyContract
{
    protected $backoffStrategy;

    public function __construct(BackoffStrategyContract $backoffStrategy)
    {
        $this->backoffStrategy = $backoffStrategy;
    }

    public function getSleepTime()
    {
        return mt_rand(0, $this->backoffStrategy->getSleepTime());
    }
}
