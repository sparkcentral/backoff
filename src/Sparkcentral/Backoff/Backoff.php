<?php
namespace Sparkcentral\Backoff;

/**
 * Simple utility trait which provides backoff / retry functionality.
 */
trait Backoff
{
    /**
     * Tries to invoke $callable the number of times specified by $attempts. Backs off / retries when
     * exception is thrown.
     *
     * @param callable $callable Callable to execute.
     * @param array $args Arguments to pass to the $callable.
     * @param int $attempts Number of retries.
     * @param array $exceptionFqns List of exception FQNs to retry on, if empty will retry on any exception.
     * @param int $wait μs to wait before first retry. By default, initial wait (after first try) is 1000μs.
     *
     * @throws \Exception
     *
     * @return mixed
     */
    protected function backoffOnException(callable $callable, array $args, $attempts, array $exceptionFqns = [], $wait = 1000)
    {
        try {

            return $callable(...$args);

        } catch (\Exception $e) {

            if ($attempts > 1 && (empty($exceptionFqns) || in_array(get_class($e), $exceptionFqns))) {
                usleep($wait);

                return $this->backoffOnException($callable, $args, $attempts - 1, $exceptionFqns, $this->increaseWaitTime($wait));
            }

            throw $e;
        }
    }

    /**
     * Backs off and retries $callable for $attempts number of attempts, or until $condition returns true. $condition function
     * is passed the result of $callable. So, for example:
     *
     * $this->backoffOnCondition([$this, 'truthOrDare'], [], 5, function($result) {
     *      return !empty($result);
     * });
     * @param callable $callable  Callable to execute.
     * @param array $args Arguments to pass to the $callable.
     * @param int $attempts Number of retries.
     * @param callable $condition If returns true then current result returned by $callable will be returned, will retry otherwise.
     * @param int $wait μs to wait before first retry. By default, initial wait (after first try) is 1000μs.
     *
     * @return mixed
     */
    protected function backoffOnCondition(callable $callable, array $args, $attempts, callable $condition, $wait = 1000)
    {
        $result = $callable(...$args);
        if ($attempts > 1 && $condition($result) !== true) {
            usleep($wait);

            return $this->backoffOnCondition($callable, $args, $attempts - 1, $condition, $this->increaseWaitTime($wait));
        }

        return $result;
    }

    /**
     * Tries to invoke $callable the number of times specified by $attempts. Backs off / retries when
     * exception is thrown that matches the callable condition.
     *
     * @param callable $callable Callable to execute.
     * @param array $args Arguments to pass to the $callable.
     * @param int $attempts Number of retries.
     * @param callable $condition Callable that takes the exception and returns true when it should backoff
     * @param int $wait μs to wait before first retry. By default, initial wait (after first try) is 1000μs.
     *
     * @throws \Exception
     *
     * @return mixed
     */
    protected function backoffOnExceptionCondition(callable $callable, array $args, $attempts, callable $condition, $wait = 1000)
    {
        try {

            return $callable(...$args);

        } catch (\Exception $e) {

            if ($attempts > 1 && $condition($e)) {
                usleep($wait);

                return $this->backoffOnExceptionCondition($callable, $args, $attempts - 1, $condition, $this->increaseWaitTime($wait));
            }

            throw $e;
        }
    }

    /**
     * @param int $wait Microseconds
     *
     * @return int
     */
    private function increaseWaitTime($wait)
    {
        if ($wait === 0) {

            return 1000;
        }

        return $wait * 2;
    }
}
