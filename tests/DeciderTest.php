<?php

namespace Ctroms\Retryable\Tests;

use Ctroms\Retryable\Decider;
use Ctroms\Retryable\Tests\TestCase;

class DeciderTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->decider = new Decider();
    }

    public function test_can_add_integer_values_to_retryable_errors()
    {
        $this->decider->addRetryableErrors(422);

        $this->assertEquals([422], $this->decider->getRetryableErrors());
    }

    public function test_can_add_string_values_to_retryable_errors()
    {
        $this->decider->addRetryableErrors('Test Exception Messasge');

        $this->assertEquals(['Test Exception Messasge'], $this->decider->getRetryableErrors());
    }

    public function test_can_add_an_array_of_values_to_retryable_errors()
    {
        $this->decider->addRetryableErrors([422, 500, 'Test Exception Messasge']);

        $this->assertEquals([422, 500, 'Test Exception Messasge'], $this->decider->getRetryableErrors());
    }

    public function test_a_decider_callback_can_be_set()
    {
        $callbackDecider = function ($exception, $attempts) { return true; };

        $this->decider->useCallback($callbackDecider);

        $this->assertEquals($callbackDecider, $this->decider->getCallback());
    }
}