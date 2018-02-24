<?php

namespace Tests\Unit;

use App\SleeperContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RetryStrategiesTest extends TestCase
{
    protected $times = [];
    protected $tries;
    protected $startTime;

    public function setup()
    {
        parent::setup();
        Carbon::setTestNow(Carbon::now());
        $this->app->bind(
            'App\SleeperContract',
            'App\TestSleeper'
        );
    }

    public function tearDown()
    {
        Carbon::setTestNow();
        parent::teardown();
    }

    /** @test */
    public function test_the_function_with_the_poop_name()
    {
        $this->markTestIncomplete('WIP');
        \Facades\App\Retry::dynamic(function () {
            // Do Something.
        }, [
            400 => Retry::CONSTANT,
            419 => Retry::LINEAR,
        ]);
    }

    public function test_constant_strategy()
    {
        $sleeps = [1, 1, 1, 1];

        \Facades\App\Retry::constant($this->getCallbackFromSleeps($sleeps));

        array_shift($this->times);
        $this->assertEquals($sleeps, $this->times);
    }

    public function test_linear_strategy()
    {
        $sleeps = [1, 2, 3, 4];

        \Facades\App\Retry::linear($this->getCallbackFromSleeps($sleeps));

        array_shift($this->times);
        $this->assertEquals($sleeps, $this->times);
    }

    public function test_exponential_strategy()
    {
        $sleeps = [2, 4, 8, 16];

        \Facades\App\Retry::exponential($this->getCallbackFromSleeps($sleeps));

        array_shift($this->times);
        $this->assertEquals($sleeps, $this->times);
    }

    public function getCallbackFromSleeps($sleeps)
    {
        return function () use ($sleeps) {
            $this->tries++;
            $hold = $this->startTime;
            $this->startTime = Carbon::now()->second;
            $this->times[] = (int)round($this->startTime - $hold);

            if ($this->tries !== count($sleeps) + 1) {
                throw new \Exception;
            }
        };
    }
}