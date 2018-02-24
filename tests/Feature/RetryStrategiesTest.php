<?php

namespace Feature;

use App\SleeperContract;
use App\TestSleeper;
use Carbon\Carbon;
use Facades\App\Retry;
use Tests\TestCase;

class RetryStrategiesTest extends TestCase
{
    protected $measuredDelays = [];
    protected $retryCount;
    protected $lastRetryTime;

    public function setup()
    {
        parent::setup();

        Carbon::setTestNow(Carbon::now());

        $this->app->bind(SleeperContract::class, TestSleeper::class);
    }

    public function tearDown()
    {
        Carbon::setTestNow();

        parent::teardown();
    }

    public function test_auto_detecting_a_strategy()
    {
        $this->markTestIncomplete('WIP');
        Retry::auto(function () {
            // Do Something.
        }, [
            400 => Retry::CONSTANT,
            419 => Retry::LINEAR,
        ]);
    }

    public function test_constant_strategy()
    {
        $delays = [1, 1, 1, 1];

        Retry::constant($this->getCallbackFromDelays($delays));

        $this->assertEquals($delays, $this->measuredDelays);
    }

    public function test_linear_strategy()
    {
        $delays = [1, 2, 3, 4];

        Retry::linear($this->getCallbackFromDelays($delays));

        $this->assertEquals($delays, $this->measuredDelays);
    }

    public function test_exponential_strategy()
    {
        $delays = [2, 4, 8, 16];

        Retry::exponential($this->getCallbackFromDelays($delays));

        $this->assertEquals($delays, $this->measuredDelays);
    }

    public function getCallbackFromDelays($delays)
    {
        $this->retryCount = 1;

        return function () use ($delays) {
            $currentRetryTime = Carbon::now()->timestamp;
            $this->retryCount++ > 1 && $this->measuredDelays[] = (int) $currentRetryTime - $this->lastRetryTime;

            while ($this->retryCount < count($delays) + 2) {
                $this->lastRetryTime = $currentRetryTime;
                throw new \Exception;
            }
        };
    }
}