<?php

namespace Ctroms\Retryable\Strategies;

use Ctroms\Retryable\Strategies\BackoffStrategy;
use Ctroms\Retryable\Strategies\BackoffStrategyContract;
use Ctroms\Retryable\Strategies\JitterStrategy;

class Strategy
{
    protected $maxRetryAttempts;
    protected $maxDuration;
    protected $base = 1000;
    protected $backoffStrategy;
    protected $jitterStrategy;

    public function base($base)
    {
        $this->base = $base;

        return $this;
    }

    public function times($retryAttempts)
    {
        $this->maxRetryAttempts = $retryAttempts;

        return $this;
    }

    public function sleep($duration)
    {
        $this->maxDuration = $duration;

        return $this;
    }

    public function backoff($backoffStrategy)
    {
        $this->backoffStrategy = $backoffStrategy;

        return $this;
    }

    public function jitter($jitterStrategy)
    {
        $this->jitterStrategy = $jitterStrategy;

        return $this;
    }

    public function usesJitter()
    {
        return is_null($this->jitterStrategy) === false;
    }

    public function getMaxAttempts()
    {
        return $this->maxRetryAttempts;
    }

    public function getMaxDuration()
    {
        return $this->maxDuration;
    }

    public function getBackoffStrategy()
    {
        return $this->backoffStrategy;
    }

    public function getJitterStrategy()
    {
        return $this->jitterStrategy;
    }

    public function getBase()
    {
        return $this->base;
    }

    public function wasModified()
    {
        return new self() != $this;
    }
}
