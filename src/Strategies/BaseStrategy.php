<?php

namespace Ctroms\Retryable\Strategies;

abstract class BaseStrategy
{
    protected $attempt;
    protected $base;

    abstract public function getSleepTime();

    public function setAttempt($attempt)
    {
        $this->attempt = $attempt;
    }

    public function setBase($base)
    {
        $this->base = $base;
    }

    public function getAttempt()
    {
        return $this->attempt;
    }
}
