# Backoff

Simple utility trait which provides backoff / retry functionality.

## Features

* Two different strategies: `backoffOnException`, `backoffOnCondition`
* You can provide list of exception classes so that retry will happen only when one of those from the list is thrown.
* You can pass custom function to backoffOnCondition which defines whether to retry operation or not.
* Retries happen with delays which grow linearly (y=x*2), you can pass custom starting delay as well.

For details please refer to documentation for `backoffOnException()`, `backoffOnCondition()` methods.

## Basic example

```php
<?php

use Sparkcentral\Backoff\Backoff;

class ExternalServiceWrapper
{
    use Backoff;

    private $externalApiClient;

    public function __construct(ExternalApiClient $client)
    {
        $this->externalApiClient = $client;
    }

    public function getById($id)
    {
        $result = $this->backoffOnException(
            [$this->externalApiClient, 'get'], // call 'get' method on externalApiClient
            [$id], // pass this argument to 'get'
            5, // try up to 5 times
            [ConnectException::class] // only retry on ConnectExceptions, will re-throw everything else
        );

        return $result->getObject();
    }
}
```

Similarly you can use `backoffOnCondition()` in case code you're trying to execute does not throw any exceptions, but (for instance) returns `null` in case of failure.

```php
<?php

use Sparkcentral\Backoff\Backoff;

class ExternalServiceWrapper
{
    use Backoff;

    private $externalApiClient;

    public function __construct(ExternalApiClient $client)
    {
        $this->externalApiClient = $client;
    }

    public function getById($id)
    {
        $result = $this->backoffOnCondition(
            [$this->externalApiClient, 'get'],
            [$id],
            5,
            function ($result) {
                return !is_null($result);
            }
        );

        return $result->getObject();
    }
}
