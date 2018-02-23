<?php

namespace Ctroms\Retryable;

use Ctroms\Retryable\SleeperContract;
use Carbon\Carbon;

class TestSleeper implements SleeperContract
{
    protected $maxSleepDuration;

    public function sleep($duration)
    {
        $duration = isset($this->maxSleepDuration) ? min($this->maxSleepDuration, $duration) : $duration;

        Carbon::setTestNow(Carbon::now()
               ->addSeconds($duration));
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