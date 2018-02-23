<?php

namespace Ctroms\Retryable\Strategies;

interface BackoffStrategyContract
{
    public function getSleepTime();
}
