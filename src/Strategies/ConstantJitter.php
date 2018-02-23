<?php

namespace Ctroms\Retryable\Strategies;

class ConstantJitter implements BackoffStrategyContract
{
    protected $backoffStrategy;

    public function __construct(BackoffStrategyContract $backoffStrategy)
    {
        $this->backoffStrategy = $backoffStrategy;
    }

    public function getSleepTime()
    {
        return $this->backoffStrategy->getSleepTime() + mt_rand(0, 1000);
    }
}
