# Retryable

Retryable is a package built for the Laravel framework that makes retrying failed requests a breeze. 

## Installation

```shell
composer install ctroms/retryable
```

## Usage

#### Default Strategy

The quickest way to get started is by simply passing a callable with your request logic to `retry()`. By default, this uses an exponential backoff with constant jitter between 0 and 1000ms and a maximum delay of 64 seconds under the hood. Simply pass a callable with your retryable logic as the argument.

```php
$response = Retry::retry(function () {
    return $this->client->request('GET', $url, $params);
});
```

### Fluent Strategy Builder

The `Retry` object exposes a fluent interface that easily allows you to build a strategy for your retrayble request.

```php
$response = Retry::errors(422)
                ->times(10)
                ->maxDelay(64000)
                ->base(1000)
                ->jitter(JitterStrategy::CONSTANT)
                ->backoff(BackoffStrategy::EXPONENTIAL)
                ->retry(function () {
                    return $this->client->request('GET', $url, $params);
                });
```

Method       | Description
------------ | ----------- 
`errors()`   | The status codes or exception messages to retry on
`times()`    | The maxumum number of times your request should be retried.
`sleep()`    | The maximum duration for a single delay
`base()`     | The base constant to be used for backoff and jitter strategies. Defaults to 1000ms if not specified
`backoff()`  | The strategy object that defines which backoff method you want to use
`jitter()`   | The strategy object that defines how to add jitter to the backoff strategy
`retry()`    | This method should be passed a callable that contains the logic of your request

### Retry on Specific Errors

To specify errors that are retryable, use the `errors()` method. If this method is not specified the default errors are '422' and '5**' status codes. 

#### Using a Status Code

```php
$response = Retry::errors(422)
                ->retry(function () {
                    return $this->client->request('GET', $url, $params);
                });
```

#### Using an Exception Message

```php
$response = Retry::errors('Something exception message we should retry on.')
                ->retry(function () {
                    return $this->client->request('GET', $url, $params);
                });
```

#### 5** Status Codes

Since 500 errors usually need to be retried, you can use the string '5**' to match all 500 level error codes.

```php
$response = Retry::errors('5**')
                ->retry(function () {
                    return $this->client->request('GET', $url, $params);
                });
```

#### Multiple Errors

To retry on multiple errors, simply pass an array of status codes and messages to the `errors()` method. 

```php
$response = Retry::errors([422,'5**', 'Something exception message we should retry on.'])
                ->retry(function () {
                    return $this->client->request('GET', $url, $params);
                });
```


### Backoff

The `backoff()` method accepts one of three strings to define your backoff strategy.

#### Constant Strategy

```php
$response = Retry::backoff(BackoffStrategy::CONSTANT)
                ->retry(function () {
                    $this->client->request('GET', $url, $params);
                });
```

#### Linear Backoff Strategy

```php
$response = Retry::backoff(BackoffStrategy::LINEAR)
                ->retry(function () {
                    $this->client->request('GET', $url, $params);
                });
```

#### Exponential Backoff Strategy

```php
$response = Retry::backoff(BackoffStrategy::EXPONENTIAL)
                ->retry(function () {
                    $this->client->request('GET', $url, $params);
                });
```


### Limit the Number of Retry Attempts

To avoid retrying a down service indefinitely, you can set a max number of attempts with the `times()` method.

```php
$response = Retry::times(10)
                ->retry(function () {
                    return $this->client->request('GET', $url, $params);
                });
```


### Limit the Delay

To prevent your strategy from increaseing delays infinitely, you can limit the maximum delay with the `maxDelay()` method. Once this limit is reached each subsequent delay will not exceed the max delay defined.

**Note that the units are in milliseconds. (E.g. 6000ms == 60s).**

```php
$response = Retry::maxDelay(6000)
                ->retry(function () {
                    return $this->client->request('GET', $url, $params);
                });
```


> #### Sharp Knives Warning.
> If you do not specify a max sleep time, the backoff delay will continue to increment infinitely. Similarly, if you do not specify a maximum number of tries, the request will continue to retry indefninitey.


### Base

You can set a new base constant for the strategy with the `base()` method. The following example sets a base of 2 on the linearBackoff strategy. In this case the first attempt delays 2 seconds, the second 4 seconds, the third 6 seconds and so on.

```php
$response = Retry::base(2000)
                ->retry(function () {
                    return $this->client->request('GET', $url, $params);
                });
```


### Jitter

Generally, you'll want to apply jitter to your backoff strategy to avoid competing with other clients using the same retry strategy. 
The `jitter()` method accepts one of three strings to define your jitter strategy. 

#### Constant Jitter Strategy

```php
$response = Retry::jitter(JitterStrategy::CONSTANT)
                ->retry(function () {
                    $this->client->request('GET', $url, $params);
                });
```


#### Equal Jitter Strategy

```php
$response = Retry::jitter(JitterStrategy::EQUAL)
                ->retry(function () {
                    $this->client->request('GET', $url, $params);
                });
```

#### Full Jitter Strategy

```php
$response = Retry::jitter(JitterStrategy::FULL)
                ->retry(function () {
                    $this->client->request('GET', $url, $params);
                });
```

The following example creates a retryable request using the an exponential backoff with constant jitter, a base of 1s, a max delay of 64s and retries a maximum of 10 times.

```php
$response = Retry::backoff(JitterStrategy::EXPONENTIAL)
                ->jitter(JitterStrategy::CONSTANT)
                ->base(1000)
                ->times(10)
                ->maxRetries(64000)
                ->retry(function () {
                    return $this->client->request('GET', $url, $params);
                });
```

### Callable Strategies

In addition to the fluent strategy builder, you can pass a callable to the `usingStrategy()` method. This can be a callback or an object with an `__invoke()` method. The exception object is passed as the first argument and a retryable object is passed as the second argument. 

> The retryable object is a simple object that keeps track of the number of times your retry strategy has been called. You can get the retry attempts using the `getRetryAttempts()` method. Your callabel should always return a boolean.

```php
$response = Retry::errors([422, '5**'])
        ->usingStrategy(function ($exception, $retryable) {
            if ($exception->getCode() == 422) {
                sleep(2);
            }
            if ($exception->getCode() >= 500) {
                sleep(4);
            }
            if ($retryable->getRetryAttempts > 5) {
                sleep(6);
            } 
        })->retry(function () {
            return $this->client->request('GET', $url, $params);
        });
```


### Callable Deciders

If the logic needed to determine if a failed request should be retried is more complicated than matching a status code or error message, you can pass a callable to the `usingDecider()` method on the `Retry` object. The exception object is passed as the first argument and a retryable object is passed as the second argument. 

```php
$response = Retry::usingDecider(function ($exception, $retryable) {
    if ($exception->getCode() == 422) {
        return true;
    }
    if ($retryable->getRetryAttempts() > 5) {
        return false;
    }
    return false;
})->backoff(BackoffStrategy::CONSTANT)
->retry(function () {
    return $this->client->request('GET', $url, $params);
})
```


## Credits

Thanks to [Caleb Porzio](http://twitter.com/calebporzio) for advice, contributions and for convincing me to build this in the first place.