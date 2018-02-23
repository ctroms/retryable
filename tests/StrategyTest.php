<?php

namespace Ctroms\Retryable\Tests;

use Ctroms\Retryable\Strategies\BaseStrategy;
use Ctroms\Retryable\Strategies\BackoffStrategy;
use Ctroms\Retryable\Strategies\BackoffStrategyContract;
use Ctroms\Retryable\Strategies\ConstantJitter;
use Ctroms\Retryable\Strategies\ConstantStrategy;
use Ctroms\Retryable\Strategies\EqualJitter;
use Ctroms\Retryable\Strategies\ExponentialStrategy;
use Ctroms\Retryable\Strategies\FullJitter;
use Ctroms\Retryable\Strategies\JitterStrategy;
use Ctroms\Retryable\Strategies\LinearStrategy;
use Ctroms\Retryable\Strategies\StrategyFacade as Strategy;
use Ctroms\Retryable\Tests\TestCase;

class StrategyTest extends TestCase
{
    public function test_fluent_strategy_interface()
    {
        $strategy = Strategy::times(5)->sleep(4)->backOff(BackoffStrategy::CONSTANT)->base(2)->jitter(JitterStrategy::CONSTANT);

        $this->assertEquals(5, $strategy->getMaxAttempts());
        $this->assertEquals(4, $strategy->getMaxDuration());
        $this->assertEquals(2, $strategy->getBase());
        $this->assertEquals(ConstantStrategy::class, $strategy->getBackoffStrategy());
        $this->assertEquals(ConstantJitter::class, $strategy->getJitterStrategy());
    }

    public function test_base_default_if_not_specified()
    {
        $strategy = Strategy::backOff(BackoffStrategy::CONSTANT);

        $this->assertEquals(1000, $strategy->getBase());
    }

    public function test_custom_backoff_strategy_object_that_implements_the_interface()
    {
        $strategy = Strategy::backOff(new class extends BaseStrategy implements BackoffStrategyContract {
            public function getSleepTime() { return; }
        });

        $this->assertInstanceOf(BackoffStrategyContract::class, $strategy->getBackoffStrategy());
    }

    public function test_custom_jitter_strategy_object_that_implements_the_interface()
    {
        $strategy = Strategy::jitter(new class extends BaseStrategy implements BackoffStrategyContract {
            public function getSleepTime() { return; }
        });

        $this->assertInstanceOf(BackoffStrategyContract::class, $strategy->getJitterStrategy());
    }

    public function test_constant_strategy_enum()
    {
        $strategy = Strategy::backOff(BackoffStrategy::CONSTANT);

        $this->assertEquals(ConstantStrategy::class, $strategy->getBackoffStrategy());
    }

    public function test_linear_strategy_enum()
    {
        $strategy = Strategy::backOff(BackoffStrategy::LINEAR);

        $this->assertEquals(LinearStrategy::class, $strategy->getBackoffStrategy());
    }

    public function test_exponential_strategy_enum()
    {
        $strategy = Strategy::backOff(BackoffStrategy::EXPONENTIAL);

        $this->assertEquals(ExponentialStrategy::class, $strategy->getBackoffStrategy());
    }

    public function test_constant_jitter_enum()
    {
        $strategy = Strategy::jitter(JitterStrategy::CONSTANT);

        $this->assertEquals(ConstantJitter::class, $strategy->getJitterStrategy());
    }

    public function test_full_jitter_enum()
    {
        $strategy = Strategy::jitter(JitterStrategy::FULL);

        $this->assertEquals(FullJitter::class, $strategy->getJitterStrategy());
    }

    public function test_equal_jitter_enum()
    {
        $strategy = Strategy::jitter(JitterStrategy::EQUAL);

        $this->assertEquals(EqualJitter::class, $strategy->getJitterStrategy());
    }

    public function test_constant_strategy()
    {
        $delays = [1000, 1000, 1000];
        $attempt = 1;
        $strategy = new ConstantStrategy();
        $strategy->setBase(1000);
        $strategy->setAttempt($attempt);
        $measuredDelays = [];

        do {
            $strategy->setAttempt($attempt++);
            $measuredDelays[] = $strategy->getSleepTime();
        } while ($attempt <= 3);

        $this->assertEquals($delays, $measuredDelays);
    }

    public function test_linear_strategy()
    {
        $delays = [1000, 2000, 3000];
        $attempt = 1;
        $strategy = new LinearStrategy();
        $strategy->setBase(1000);
        $strategy->setAttempt($attempt);
        $measuredDelays = [];

        do {
            $strategy->setAttempt($attempt++);
            $measuredDelays[] = $strategy->getSleepTime();
        } while ($attempt <= 3);

        $this->assertEquals($delays, $measuredDelays);
    }

    public function test_exponential_strategy()
    {
        $delays = [2000, 4000, 8000];
        $attempt = 1;
        $strategy = new ExponentialStrategy();
        $strategy->setBase(1000);
        $strategy->setAttempt($attempt);
        $measuredDelays = [];

        do {
            $strategy->setAttempt($attempt++);
            $measuredDelays[] = $strategy->getSleepTime();
        } while ($attempt <= 3);

        $this->assertEquals($delays, $measuredDelays);
    }

    public function test_constant_jitter()
    {
        $delays = [2000, 4000, 8000];
        $firstRunMeasuredDelays = [];
        $secondRunMeasuredDelays = [];
        $strategy = new ExponentialStrategy();
        $strategy->setBase(1000);

        $attempt = 1;
        $strategy->setAttempt($attempt);
        do {
            $strategy->setAttempt($attempt++);
            $firstRunMeasuredDelays[] = (new ConstantJitter($strategy))->getSleepTime();
        } while ($attempt <= 3);

        $attempt = 1;
        $strategy->setAttempt($attempt);
        do {
            $strategy->setAttempt($attempt++);
            $secondRunMeasuredDelays[] = (new ConstantJitter($strategy))->getSleepTime();
        } while ($attempt <= 3);

        $this->assertInRange($firstRunMeasuredDelays[0], 2000, 3000);
        $this->assertInRange($firstRunMeasuredDelays[1], 4000, 5000);
        $this->assertInRange($firstRunMeasuredDelays[2], 8000, 9000);
        $this->assertNotEquals($firstRunMeasuredDelays[0], $secondRunMeasuredDelays[0]);
        $this->assertNotEquals($firstRunMeasuredDelays[1], $secondRunMeasuredDelays[1]);
        $this->assertNotEquals($firstRunMeasuredDelays[2], $secondRunMeasuredDelays[2]);
    }

    public function test_equal_jitter()
    {
        $delays = [1000, 1000, 1000];
        $firstRunMeasuredDelays = [];
        $secondRunMeasuredDelays = [];
        $strategy = new ExponentialStrategy();
        $strategy->setBase(1000);

        $attempt = 1;
        $strategy->setAttempt($attempt);
        do {
            $strategy->setAttempt($attempt++);
            $firstRunMeasuredDelays[] = (new EqualJitter($strategy))->getSleepTime();
        } while ($attempt <= 3);

        $attempt = 1;
        $strategy->setAttempt($attempt);
        do {
            $strategy->setAttempt($attempt++);
            $secondRunMeasuredDelays[] = (new EqualJitter($strategy))->getSleepTime();
        } while ($attempt <= 3);

        $this->assertInRange($firstRunMeasuredDelays[0], 1000, 2000);
        $this->assertInRange($firstRunMeasuredDelays[1], 2000, 4000);
        $this->assertInRange($firstRunMeasuredDelays[2], 4000, 8000);
        $this->assertNotEquals($firstRunMeasuredDelays[0], $secondRunMeasuredDelays[0]);
        $this->assertNotEquals($firstRunMeasuredDelays[1], $secondRunMeasuredDelays[1]);
        $this->assertNotEquals($firstRunMeasuredDelays[2], $secondRunMeasuredDelays[2]);
    }

    public function test_full_jitter()
    {
        $delays = [1000, 1000, 1000];
        $firstRunMeasuredDelays = [];
        $secondRunMeasuredDelays = [];
        $strategy = new ExponentialStrategy();
        $strategy->setBase(1000);

        $attempt = 1;
        $strategy->setAttempt($attempt);
        do {
            $strategy->setAttempt($attempt++);
            $firstRunMeasuredDelays[] = (new FullJitter($strategy))->getSleepTime();
        } while ($attempt <= 3);

        $attempt = 1;
        $strategy->setAttempt($attempt);
        do {
            $strategy->setAttempt($attempt++);
            $secondRunMeasuredDelays[] = (new FullJitter($strategy))->getSleepTime();
        } while ($attempt <= 3);

        $this->assertInRange($firstRunMeasuredDelays[0], 0, 2000);
        $this->assertInRange($firstRunMeasuredDelays[1], 0, 4000);
        $this->assertInRange($firstRunMeasuredDelays[2], 0, 8000);
        $this->assertNotEquals($firstRunMeasuredDelays[0], $secondRunMeasuredDelays[0]);
        $this->assertNotEquals($firstRunMeasuredDelays[1], $secondRunMeasuredDelays[1]);
        $this->assertNotEquals($firstRunMeasuredDelays[2], $secondRunMeasuredDelays[2]);
    }
}