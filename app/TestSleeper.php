<?php

namespace App;

use Carbon\Carbon;

class TestSleeper implements SleeperContract
{
    public function sleep($duration)
    {
        Carbon::setTestNow(Carbon::now()->addSeconds($duration));
    }
}