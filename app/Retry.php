<?php

namespace App;

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
            sleep(1);
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
            sleep($sleep++);
            goto beginning;
        }
    }
}