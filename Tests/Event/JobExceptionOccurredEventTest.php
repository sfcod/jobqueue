<?php

namespace SfCod\QueueBundle\Tests\Event;

use Exception;
use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Event\JobExceptionOccurredEvent;
use SfCod\QueueBundle\Job\JobContractInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class JobExceptionOccurredEventTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\Event
 */
class JobExceptionOccurredEventTest extends TestCase
{
    /**
     * Test event
     */
    public function testEvent()
    {
        $message = uniqid('message_');
        $connectionName = uniqid('connection_');
        $job = $this->createMock(JobContractInterface::class);
        $exception = new Exception($message);

        $event = new JobExceptionOccurredEvent($connectionName, $job, $exception);

        self::assertInstanceOf(Event::class, $event);
        self::assertEquals($connectionName, $event->getConnectionName());
        self::assertEquals($job, $event->getJob());
        self::assertEquals($exception, $event->getException());
        self::assertEquals($exception->getMessage(), $event->getException()->getMessage());
    }
}
