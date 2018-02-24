<?php

namespace App;

use App\SleeperContract;

class Sleeper implements SleeperContract
{
    public function sleep($duration)
    {
        sleep($duration);
    }
}
