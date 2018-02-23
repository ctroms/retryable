<?php

namespace Ctroms\Retryable\Strategies;

use Ctroms\Retryable\Strategies\BackoffStrategyContract;

class EqualJitter implements BackoffStrategyContract
{
    protected $backoffStrategy;

    public function __construct(BackoffStrategyContract $backoffStrategy)
    {
        $this->backoffStrategy = $backoffStrategy;
    }

    public function getSleepTime()
    {
        $sleepTime = $this->backoffStrategy->getSleepTime();

        return $sleepTime / 2 + mt_rand(0, $sleepTime / 2);
    }
}
