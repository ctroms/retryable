<?php

namespace Ctroms\Retryable;

use Ctroms\Retryable\Decider;
use Ctroms\Retryable\Retryable;
use Ctroms\Retryable\SleeperContract as Sleeper;
use Ctroms\Retryable\Strategies\BackoffStrategy;
use Ctroms\Retryable\Strategies\JitterStrategy;
use Ctroms\Retryable\Strategies\Strategy;

class Retry
{
    protected $decider;
    protected $strategy;
    protected $retryable;

    public function __construct(Decider $decider, Strategy $strategy)
    {
        $this->decider = $decider;
        $this->strategy = $strategy;
    }

    public function usingStrategy($strategy)
    {
        $this->strategy = $strategy;

        return $this;
    }

    public function usingDecider($decider)
    {
        is_callable($decider)
            ? $this->decider->useCallback($decider)
            : $this->decider = $decider;

        return $this;
    }

    public function onErrors($errors)
    {
       $this->decider->addRetryableErrors($errors);

        return $this;
    }

    public function times($times)
    {
        $this->strategy->times($times);

        return $this;
    }

    public function maxDelay($delay)
    {
        $this->strategy->sleep($delay);

        return $this;
    }

    public function backoff($backoffStrategy)
    {
        $this->strategy->backoff($backoffStrategy);

        return $this;
    }

    public function jitter($jitterStrategy)
    {
        $this->strategy->jitter($jitterStrategy);

        return $this;
    }

    public function base($base)
    {
        $this->strategy->base($base);

        return $this;
    }

    public function retry($callable)
    {
        $this->setDefaults();
        $retryable = Retryable::make($this->strategy);

        beginning:
        try {
            return $callable();
        } catch (\Throwable $e) {
            if (! $this->decider->shouldRetry($e, $retryable)) {
                throw $e;
            }
            $retryable->handle($e);
            goto beginning;
        }
    }

    public function getRetryableErrors()
    {
        return $this->decider->getRetryableErrors();
    }

    public function getStrategy()
    {
        return $this->strategy;
    }

    protected function setDefaults()
    {
        if (empty($this->decider->getRetryableErrors())) {
            $this->onErrors([422, '5**']);
        }

        if (! is_callable($this->strategy) && ! $this->strategy->wasModified()) {
            $this->setDefaultStrategy();
        }
    }

    protected function setDefaultStrategy()
    {
        $this->strategy->base(1000)->times(10)->sleep(16000)->backOff(BackoffStrategy::EXPONENTIAL)->jitter(JitterStrategy::CONSTANT);
    }
}