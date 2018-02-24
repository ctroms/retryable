<?php

namespace App;

use Illuminate\Support\Carbon;

class TestSleeper implements SleeperContract
{
    public function sleep($duration)
    {
        Carbon::setTestNow(Carbon::now()->addSeconds($duration));
    }
}