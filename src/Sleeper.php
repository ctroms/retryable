<?php

namespace Ctroms\Retryable;

use Ctroms\Retryable\SleeperContract;

class Sleeper implements SleeperContract
{
    protected $maxSleepDuration;

    public function sleep($duration)
    {
        $sleep = isset($this->maxSleepDuration) ? min($this->maxSleepDuration, $duration) : $duration;
        sleep($sleep / 1000);
    }

    public function setMaxSleepDuration($duration)
    {
        $this->maxSleepDuration = $duration;
    }

    public function getMaxSleepDuration()
    {
        return $this->maxSleepDuration;
    }
}
