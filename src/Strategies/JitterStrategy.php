<?php

namespace Ctroms\Retryable\Strategies;

use Ctroms\Retryable\Strategies\ConstantJitter;
use Ctroms\Retryable\Strategies\EqualJitter;
use Ctroms\Retryable\Strategies\FullJitter;

class JitterStrategy
{
    const CONSTANT = ConstantJitter::class;
    const EQUAL = EqualJitter::class;
    const FULL = FullJitter::class;
}
