<?php

namespace Ctroms\Retryable;

use Ctroms\Retryable\Exceptions\MaxRetriesExceededException;

class Decider
{
    protected $retryableErrors = [];
    protected $callbackDecider;

    public function shouldRetry(\Throwable $exception, $retryable)
    {
        if ($this->isCallableDecider()) {
            return call_user_func_array($this->callbackDecider, [$exception, $retryable]);
        }

        if ($this->isMaxRetriesExceeded($retryable)) {
            throw new MaxRetriesExceededException();
        }

        if ($this->exceptionCodeIsRetryable($exception) || $this->exceptionMessageIsRetryable($exception)) {
            return true;
        }

        return false;
    }

    public function isMaxRetriesExceeded($retryable)
    {
        return (! is_callable($retryable->getStrategy())
                && $retryable->getStrategy()->getMaxAttempts()
                && $retryable->getRetryAttempts() + 1 > $retryable->getStrategy()->getMaxAttempts());
    }

    public function exceptionCodeIsRetryable($exception)
    {
        return (($exception->getCode() >= 500 && in_array('5**', $this->retryableErrors))
                || in_array($exception->getCode(), $this->retryableErrors));
    }

    public function exceptionMessageIsRetryable($exception)
    {
        return in_array($exception->getMessage(), $this->retryableErrors);
    }

    public function useCallback($callback)
    {
        $this->callbackDecider = $callback;
    }

    public function addRetryableErrors($errors)
    {
        if (is_int($errors) || is_string($errors)) {
            $this->retryableErrors[] = $errors;
        }

        if (is_array($errors)) {
            array_map(function ($error) {
                $this->retryableErrors[] = $error;
            }, $errors);
        }
    }

    public function isCallableDecider()
    {
        return isset($this->callbackDecider);
    }

    public function getRetryableErrors()
    {
        return $this->retryableErrors;
    }

    public function getCallback()
    {
        return $this->callbackDecider;
    }
}
