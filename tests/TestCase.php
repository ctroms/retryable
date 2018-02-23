<?php

namespace Ctroms\Retryable\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Ctroms\Retryable\RetryFacade;
use Ctroms\Retryable\RetryableServiceProvider;

class TestCase extends Orchestra
{
    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        parent::setUp();

        // Your code here
    }

    protected function getPackageProviders($app)
    {
        return [RetryableServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Retry' => RetryFacade::class,
        ];
    }

    public static function assertInRange($number, $floor, $ceil, $message = '')
    {
        try {
            self::assertThat($number >= $floor && $number <= $ceil, self::isTrue(), $message);
        } catch (\Throwable $e) {
            throw new \Exception(sprintf('Failed asserting that %s is in range [%s, %s]', $number, $floor, $ceil));
        }
    }
}
