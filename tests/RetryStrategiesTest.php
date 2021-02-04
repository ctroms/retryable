<?php

namespace Ctroms\Retryable\Tests;

use Carbon\Carbon;
use Ctroms\Retryable\Exceptions\MaxRetriesExceededException;
use Ctroms\Retryable\RetryFacade as Retry;
use Ctroms\Retryable\Sleeper;
use Ctroms\Retryable\SleeperContract;
use Ctroms\Retryable\Strategies\BackoffStrategy;
use Ctroms\Retryable\Strategies\ConstantJitter;
use Ctroms\Retryable\Strategies\EqualJitter;
use Ctroms\Retryable\Strategies\FullJitter;
use Ctroms\Retryable\Strategies\JitterStrategy;
use Ctroms\Retryable\TestSleeper;
use Ctroms\Retryable\Tests\TestCase;
use Ctroms\Retryable\Strategies\StrategyFacade as Strategy;

class RetryStrategiesTest extends TestCase
{
    protected $measuredDelays = [];
    protected $retryCount;
    protected $lastRetryTime;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::now());

        $this->app->bind(SleeperContract::class, TestSleeper::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::teardown();
    }

    public function test_can_set_error_code_to_retry_on()
    {
        $retryableErrors = [422, 500];
        $delays = [1000, 2000, 3000, 4000];
        $testStrategy = $this->getTestStrategyCallback();

        $retryable = Retry::usingStrategy($testStrategy)->onErrors($retryableErrors);

        $retryable->retry($this->getCallbackFromDelays($delays, new \Exception('', 422)));
        $this->assertCount(4, $this->measuredDelays);
        $this->resetMeasuredDelays();

        $retryable->retry($this->getCallbackFromDelays($delays, new \Exception('', 500)));
        $this->assertCount(4, $this->measuredDelays);
        $this->resetMeasuredDelays();

        try {
           $retryable->retry($this->getCallbackFromDelays($delays, new \Exception('', 900)));
        } catch (\Throwable $e) {
            $this->assertEquals(900, $e->getCode());
            $this->assertCount(0, $this->measuredDelays);
            return;
        }

        $this->fail('Expected exception not caught');
    }

    /** @test */
    public function test_on_errors_can_accept_single_integer_arguments()
    {
        $retry = Retry::onErrors(422);

        $this->assertEquals([422], $retry->getRetryableErrors());
    }

    /** @test */
    public function test_on_errors_can_accept_string_argument()
    {
        $retry = Retry::onErrors('Some exception message');

        $this->assertEquals(['Some exception message'], $retry->getRetryableErrors());
    }

    /** @test */
    public function test_on_errors_can_accept_array_of_errors_arguments()
    {
        $retry = Retry::onErrors([422, '5**', 'Some exception message']);

        $this->assertEquals([422, '5**', 'Some exception message'], $retry->getRetryableErrors());
    }

    public function test_retry_using_decider()
    {
        $delays = [1000, 2000, 3000, 4000];
        $decider = new \Ctroms\Retryable\Decider();
        $decider->addRetryableErrors([422, '5**', 'Some exception message']);
        $testStrategy = $this->getTestStrategyCallback();

        Retry::usingDecider($decider)
                ->usingStrategy($testStrategy)
                ->retry($this->getCallbackFromDelays($delays, new \Exception('422 Exception', 422)));
        $this->assertCount(4, $this->measuredDelays);
        $this->resetMeasuredDelays();

        Retry::usingDecider($decider)
                ->usingStrategy($testStrategy)
                ->retry($this->getCallbackFromDelays($delays, new \Exception('500 Exception', 500)));
        $this->assertCount(4, $this->measuredDelays);
        $this->resetMeasuredDelays();

        Retry::usingDecider($decider)
                ->usingStrategy($testStrategy)
                ->retry($this->getCallbackFromDelays($delays, new \Exception('Some exception message', 999)));
        $this->assertCount(4, $this->measuredDelays);
        $this->resetMeasuredDelays();

        try {
            Retry::usingDecider($decider)
                ->usingStrategy($testStrategy)
                ->retry($this->getCallbackFromDelays($delays, new \Exception('Unauthorized', 401)));
        } catch (\Throwable $e) {
            $this->assertEquals(401, $e->getCode());
            $this->assertEquals('Unauthorized', $e->getMessage());
            $this->assertTrue(true);
            return;
        }

        $this->fail('Exception Not Thrown');
    }

    public function test_retry_using_decider_with_passing_callback()
    {
        $delays = [1, 2, 3, 4];
        $testStrategy = $this->getTestStrategyCallback();

        Retry::usingDecider(function($exception, $retryable) {
            if ($retryable->getRetryAttempts() > 5) {
                return false;
            }
            if ($exception->getCode() == 422) {
                return true;
            }
            return false;
        })->usingStrategy($testStrategy)
          ->retry($this->getCallbackFromDelays($delays, new \Exception('This is our test exception', 422)));

        $this->assertCount(4, $this->measuredDelays);
    }

    public function test_retry_using_decider_with_failing_callback()
    {
        $delays = [1000, 2000, 3000, 4000];
        $testStrategy = $this->getTestStrategyCallback();

        try {
            Retry::usingDecider(function($exception, $retryable) {
                if ($retryable->getRetryAttempts() > 5) {
                    return false;
                }
                if ($exception->getCode() == 422) {
                    return false;
                }
                return false;
            })->usingStrategy($testStrategy)
            ->retry($this->getCallbackFromDelays($delays, new \Exception('This is our test exception', 422)));
        } catch (\Exception $e) {
            $this->assertEquals(422, $e->getCode());
            $this->assertEquals('This is our test exception', $e->getMessage());
            $this->assertEmpty($this->measuredDelays);
            return;
        }

        $this->fail('Expected exception was not caught');
    }

    public function test_the_request_exception_is_thrown_if_the_code_is_not_retriable()
    {
        $delays = [1, 2, 3, 4];
        $decider = function($exception, $currentAttempt) { return false; };
        $testStrategy = $this->getTestStrategyCallback();

        try {
            Retry::usingDecider($decider)
                ->usingStrategy($testStrategy)
                ->retry($this->getCallbackFromDelays($delays, new \Exception('Test error message', 900)));
        } catch (\Throwable $e) {
            $this->assertEquals(900, $e->getCode());
            $this->assertEquals('Test error message', $e->getMessage());
            $this->assertEmpty($this->measuredDelays);
            return;
        }

        $this->fail('Expected exception not caught');
    }

    public function test_retry_using_strategy()
    {
        $delays = [2000, 4000, 8000, 16000];

        $strategy = Strategy::backOff(BackoffStrategy::EXPONENTIAL)->jitter(JitterStrategy::CONSTANT);

        Retry::onErrors([422])
            ->usingStrategy($strategy)
            ->retry($this->getCallbackFromDelays($delays, new \Exception('', 422)));

        $this->assertInRange($this->measuredDelays[0], 2000, 3000);
        $this->assertInRange($this->measuredDelays[1], 4000, 5000);
        $this->assertInRange($this->measuredDelays[2], 8000, 9000);
        $this->assertInRange($this->measuredDelays[3], 16000, 17000);
    }

    public function test_retry_using_strategy_with_callback()
    {
        $sleeper = app(SleeperContract::class);
        $delays = [500, 1000, 1500, 2000];

        Retry::onErrors(422)->usingStrategy(function ($exception, $attemptCount) use ($sleeper) {
            return $sleeper->sleep(500 * $attemptCount);
        })->retry($this->getCallbackFromDelays($delays, new \Exception('422 Error', 422)));

        $this->assertEquals($delays, $this->measuredDelays);
    }

    public function test_max_retries_exceeded_exception_is_thrown_when_max_retries_are_exceeded()
    {
        $delays = [2000, 4000, 8000, 16000];

        try {
            Retry::onErrors(422)->backOff(BackoffStrategy::EXPONENTIAL)->times(2)->retry($this->getCallbackFromDelays($delays, new \Exception('', 422)));
        } catch (\Throwable $e) {
            $this->assertInstanceOf(MaxRetriesExceededException::class, $e);
            $this->assertCount(2, $this->measuredDelays);
            $this->assertEquals([2000, 4000], $this->measuredDelays);
            return;
        }

        $this->fail('Expected MaxRetriesExceededException Exception not thrown');
    }

    public function test_retries_continue_at_max_retry_time_when_max_retry_time_is_reached()
    {
        $delays = [2000, 4000, 8000, 16000, 32000];

        Retry::onErrors(422)
                ->backOff(BackoffStrategy::EXPONENTIAL)
                ->maxDelay(12000)
                ->retry($this->getCallbackFromDelays($delays, new \Exception('', 422)));

        $this->assertEquals([2000, 4000, 8000, 12000, 12000], $this->measuredDelays);
    }

    public function test_base_constant_default()
    {
        $delays = [1000, 2000, 3000, 4000];

        Retry::onErrors(422)
                ->backOff(BackoffStrategy::LINEAR)
                ->retry($this->getCallbackFromDelays($delays, new \Exception('', 422)));

        $this->assertEquals([1000, 2000, 3000, 4000], $this->measuredDelays);
    }

    public function test_base_constant_speficied()
    {
        $delays = [1000, 2000, 3000, 4000];

        Retry::onErrors(422)
                ->base(2000)
                ->backOff(BackoffStrategy::LINEAR)
                ->retry($this->getCallbackFromDelays($delays, new \Exception('', 422)));

        $this->assertEquals([2000, 4000, 6000, 8000], $this->measuredDelays);
    }

    public function test_strategy_without_jitter_strategy()
    {
        $delays = [2000, 4000, 8000, 16000];

        Retry::onErrors(422)->backOff(BackoffStrategy::EXPONENTIAL)->retry($this->getCallbackFromDelays($delays, new \Exception('', 422)));

        $this->assertEquals($delays, $this->measuredDelays);
    }

    public function test_strategy_with_jitter()
    {
        $delays = [2000, 4000, 8000, 16000];

        Retry::onErrors(422)
                ->backOff(BackoffStrategy::EXPONENTIAL)
                ->jitter(JitterStrategy::CONSTANT)
                ->retry($this->getCallbackFromDelays($delays, new \Exception('', 422)));

        $this->assertInRange($this->measuredDelays[0], 2000, 3000);
        $this->assertInRange($this->measuredDelays[1], 4000, 5000);
        $this->assertInRange($this->measuredDelays[2], 8000, 9000);
        $this->assertInRange($this->measuredDelays[3], 16000, 17000);
    }

    public function test_default_errors_are_used_if_it_is_not_provided()
    {
        $delays = [2000, 4000, 8000, 16000];

        Retry::backoff(BackoffStrategy::EXPONENTIAL)->retry($this->getCallbackFromDelays($delays, new \Exception('Test 422 Exception Message', 422)));

        $this->assertInRange($this->measuredDelays[0], 2000, 3000);
        $this->assertInRange($this->measuredDelays[1], 4000, 5000);
        $this->assertInRange($this->measuredDelays[2], 8000, 9000);
        $this->assertInRange($this->measuredDelays[3], 16000, 17000);
        $this->resetMeasuredDelays();

        Retry::backoff(BackoffStrategy::EXPONENTIAL)->retry($this->getCallbackFromDelays($delays, new \Exception('Test 500 Exception Message', 500)));

        $this->assertInRange($this->measuredDelays[0], 2000, 3000);
        $this->assertInRange($this->measuredDelays[1], 4000, 5000);
        $this->assertInRange($this->measuredDelays[2], 8000, 9000);
        $this->assertInRange($this->measuredDelays[3], 16000, 17000);
        $this->resetMeasuredDelays();

        Retry::backoff(BackoffStrategy::EXPONENTIAL)->retry($this->getCallbackFromDelays($delays, new \Exception('Test 503 Exception Message', 503)));

        $this->assertInRange($this->measuredDelays[0], 2000, 3000);
        $this->assertInRange($this->measuredDelays[1], 4000, 5000);
        $this->assertInRange($this->measuredDelays[2], 8000, 9000);
        $this->assertInRange($this->measuredDelays[3], 16000, 17000);
        $this->resetMeasuredDelays();

        try {
            Retry::retry($this->getCallbackFromDelays($delays, new \Exception('Test Non-Retryable Exception Message', 400)));
        } catch (\Throwable $e) {
            $this->assertCount(0, $this->measuredDelays);
            $this->assertEquals('Test Non-Retryable Exception Message', $e->getMessage());
            return;
        }
    }

    public function test_a_default_strategy_is_used_if_it_is_not_provided()
    {
        $delays = [2000, 4000, 8000, 16000];

        Retry::onErrors(422)->retry($this->getCallbackFromDelays($delays, new \Exception('', 422)));

        $this->assertInRange($this->measuredDelays[0], 2000, 3000);
        $this->assertInRange($this->measuredDelays[1], 4000, 5000);
        $this->assertInRange($this->measuredDelays[2], 8000, 9000);
        $this->assertInRange($this->measuredDelays[3], 16000, 17000);
    }

    public function test_a_default_strategy_with_default_errors_are_used_if_they_are_not_provided()
    {
        $delays = [2000, 4000, 8000, 16000];

        Retry::retry($this->getCallbackFromDelays($delays, new \Exception('Test 422 Exception Message', 422)));

        $this->assertInRange($this->measuredDelays[0], 2000, 3000);
        $this->assertInRange($this->measuredDelays[1], 4000, 5000);
        $this->assertInRange($this->measuredDelays[2], 8000, 9000);
        $this->assertInRange($this->measuredDelays[3], 16000, 17000);
        $this->resetMeasuredDelays();

        Retry::retry($this->getCallbackFromDelays($delays, new \Exception('Test 500 Exception Message', 500)));

        $this->assertInRange($this->measuredDelays[0], 2000, 3000);
        $this->assertInRange($this->measuredDelays[1], 4000, 5000);
        $this->assertInRange($this->measuredDelays[2], 8000, 9000);
        $this->assertInRange($this->measuredDelays[3], 16000, 17000);
        $this->resetMeasuredDelays();

        Retry::retry($this->getCallbackFromDelays($delays, new \Exception('Test 503 Exception Message', 503)));

        $this->assertInRange($this->measuredDelays[0], 2000, 3000);
        $this->assertInRange($this->measuredDelays[1], 4000, 5000);
        $this->assertInRange($this->measuredDelays[2], 8000, 9000);
        $this->assertInRange($this->measuredDelays[3], 16000, 17000);
        $this->resetMeasuredDelays();

        try {
            Retry::retry($this->getCallbackFromDelays($delays, new \Exception('Test Non-Retryable Exception Message', 400)));
        } catch (\Throwable $e) {
            $this->assertCount(0, $this->measuredDelays);
            $this->assertEquals('Test Non-Retryable Exception Message', $e->getMessage());
            return;
        }

        $this->fail('Expected Exception was not caught');
    }

    public function test_the_real_sleeper()
    {
        $this->app->bind(SleeperContract::class, Sleeper::class);
        $delays = [1000, 2000];
        $strategy = Strategy::times(2)->sleep(2000)->backOff(BackoffStrategy::LINEAR);

        Retry::onErrors(422)
                ->usingStrategy($strategy)
                ->retry($this->getCallbackFromDelays($delays, new \Exception('', 422), false));

        $this->assertEquals($delays, $this->measuredDelays);
    }

    public function getCallbackFromDelays($delays, $exception = null, $useCarbon = true)
    {
        $this->retryCount = 1;

        return function () use ($delays, $exception, $useCarbon) {
            if ($useCarbon) {
                $currentRetryTime = Carbon::now()->timestamp;
                $this->retryCount++ > 1 && $this->measuredDelays[] = (int) $currentRetryTime - $this->lastRetryTime;
            } else {
                $currentRetryTime = microtime(true);
                $this->retryCount++ > 1 && $this->measuredDelays[] = (int) ($currentRetryTime - $this->lastRetryTime) * 1000;
            }

            while ($this->retryCount < count($delays) + 2) {
                $this->lastRetryTime = $currentRetryTime;
                if (is_null($exception)) {
                    throw new \Exception;
                }

                throw $exception;
            }
        };
    }

    public function resetMeasuredDelays()
    {
        $this->measuredDelays = [];
    }

    public function getTestStrategyCallback()
    {
        return function ($exception, $retryAttempts) {
            return $retryAttempts++;
        };
    }
}
