<?php
namespace Sparkcentral\Backoff;

class BackoffTest extends \PHPUnit_Framework_TestCase
{
    use Backoff;

    public function testBackoffOnException()
    {
        $action = function() {
            static $c = 1;
            while ($c < 3) {
                $c++;
                throw new \DomainException();
            }

            return $c;
        };


        $result = $this->backoffOnException($action, [], 3, [\DomainException::class]);
        $this->assertEquals(3, $result);
    }

    public function testItRethrowsException()
    {
        $action = function() {
            throw new \DomainException();
        };

        $this->setExpectedException(\DomainException::class);
        $result = $this->backoffOnException($action, [], 1, [\DomainException::class]);
    }

    public function testItBacksoffOnSpecificExceptions()
    {
        $action = function() {
            throw new \RuntimeException();
        };

        $this->setExpectedException(\RuntimeException::class);
        $result = $this->backoffOnException($action, [], 1, [\DomainException::class]);
    }

    public function testItRetriesOnAnyException()
    {
        $action = function() {
            static $c = 1;
            while ($c < 3) {
                $c++;
                throw new \DomainException();
            }

            return $c;
        };

        $result = $this->backoffOnException($action, [], 3);
        $this->assertEquals(3, $result);
    }

    public function testItPassArguments()
    {
        $time = new \DateTime();
        $message = 'Current time is: ';
        $action = function($message, \DateTime $time) {
            return $message . $time->format('c');
        };


        $result = $this->backoffOnException($action, [$message, $time], 1, [\DomainException::class]);
        $this->assertEquals($message . $time->format('c'), $result);
    }

    public function testItWaitsBetweenRetries()
    {
        $action = function() {
            static $c = 1;
            while ($c < 6) {
                $c++;
                throw new \DomainException();
            }

            return $c;
        };

        $start = microtime(true);
        $result = $this->backoffOnException($action, [], 6, [\DomainException::class]);
        $end = microtime(true);
        $diffInMsecs = ($end - $start) * 1000;
        $this->assertGreaterThan(31, $diffInMsecs); // linear growth (y=x*2) will give 31msec of delays after 6 attempts
    }

    public function testItBacksOffOnCondition()
    {
        $action = function() {
            static $c = 1;
            while ($c < 3) {
                $c++;
                return null;
            }

            return $c;
        };

        $condition = function($result) { return !is_null($result); };


        $result = $this->backoffOnCondition($action, [], 3, $condition);
        $this->assertEquals(3, $result);
    }
}
