<?php

namespace Ctroms\Retryable;

interface SleeperContract
{
    public function sleep($duration);
    public function setMaxSleepDuration($duration);
    public function getMaxSleepDuration();
}
