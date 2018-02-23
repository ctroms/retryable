<?php

namespace Ctroms\Retryable\Strategies;

use Ctroms\Retryable\Strategies\ConstantStrategy;
use Ctroms\Retryable\Strategies\ExponentialStrategy;
use Ctroms\Retryable\Strategies\LinearStrategy;

class BackoffStrategy
{
    const CONSTANT = ConstantStrategy::class;
    const LINEAR = LinearStrategy::class;
    const EXPONENTIAL = ExponentialStrategy::class;
}
