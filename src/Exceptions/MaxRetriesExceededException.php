<?php

namespace Ctroms\Retryable\Exceptions;

use Exception;

class MaxRetriesExceededException extends Exception
{
    public function __construct($message = 'Max Retry Limit Exceeded')
    {
        parent::__construct($message);
    }
}
