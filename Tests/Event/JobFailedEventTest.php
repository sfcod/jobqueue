<?php

namespace SfCod\QueueBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Event\JobFailedEvent;
use SfCod\QueueBundle\Job\JobContractInterface;
use Exception;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class JobFailedEventTest
 * @author Virchenko Maksim <muslim1992@gmail.com>
 * @package SfCod\QueueBundle\Tests\Event
 */
class JobFailedEventTest extends TestCase
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

        $event = new JobFailedEvent($connectionName, $job, $exception);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals($connectionName, $event->getConnectionName());
        $this->assertEquals($job, $event->getJob());
        $this->assertEquals($exception, $event->getException());
        $this->assertEquals($exception->getMessage(), $event->getException()->getMessage());
    }
}