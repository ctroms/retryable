<?php

namespace App;

use \Facades\App\SleeperContract as Sleeper;

class Retry
{
    public function dynamic()
    {

    }

    public function constant($callback)
    {
        beginning:
        try {
            return $callback();
        } catch (\Throwable $e) {
            Sleeper::sleep(1);
            goto beginning;
        }
    }

    public function linear($callback)
    {
        $sleep = 1;
        beginning:
        try {
            return $callback();
        } catch (\Throwable $e) {
            Sleeper::sleep($sleep++);
            goto beginning;
        }
    }

    public function exponential($callback)
    {
        $base = 1;
        $attempt = 1;
        beginning:
        try {
            return $callback();
        } catch (\Throwable $e) {
            Sleeper::sleep($base * pow(2, $attempt));
            $attempt++;
            goto beginning;
        }
    }
}