<?php

namespace Ctroms\Retryable;

use Ctroms\Retryable\SleeperContract as Sleeper;
use Ctroms\Retryable\Strategies\BackoffStrategy;

class Retryable
{
    protected $retryAttempts = 0;
    protected $strategy = BackoffStrategy::EXPONENTIAL;
    protected $sleeper;

    public function __construct($strategy, Sleeper $sleeper = null)
    {
        $this->sleeper = $sleeper;
        $this->strategy = $strategy;
    }

    public static function make($strategy, Sleeper $sleeper = null)
    {
        if (is_callable($strategy)) {
            return new self($strategy);
        }

        if (! $strategy->getBackoffStrategy()) {
            $strategy->backoff(BackoffStrategy::EXPONENTIAL);
        }

        $sleeper = app(Sleeper::class);
        $sleeper->setMaxSleepDuration($strategy->getMaxDuration());

        return new self($strategy, $sleeper);
    }

    public function handle($exception = null)
    {
        $this->retryAttempts++;

        if (is_callable($this->strategy)) {
            return call_user_func_array($this->strategy, [$exception, $this->retryAttempts]);
        }

        if ($this->strategy->usesJitter()) {
            $jitter = $this->strategy->getJitterStrategy();
            $this->sleeper->sleep(
                (new $jitter($this->getBackoffStrategyInstance()))->getSleepTime()
            );
            return;
        }

        $this->sleeper->sleep(
            $this->getBackoffStrategyInstance()->getSleepTime()
        );
    }

    public function getBackoffStrategyInstance()
    {
        $backoffClass = $this->strategy->getBackoffStrategy();
        $backoff = new $backoffClass;
        $backoff->setBase($this->strategy->getBase());
        $backoff->setAttempt($this->retryAttempts);

        return $backoff;
    }

    public function getRetryAttempts()
    {
        return $this->retryAttempts;
    }

    public function getStrategy()
    {
        return $this->strategy;
    }
}
