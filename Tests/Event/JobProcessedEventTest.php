<?php

namespace SfCod\QueueBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Event\JobProcessedEvent;
use SfCod\QueueBundle\Job\JobContractInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class JobProcessedEventTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\Event
 */
class JobProcessedEventTest extends TestCase
{
    /**
     * Test event
     */
    public function testEvent()
    {
        $connectionName = uniqid('connection_');
        $job = $this->createMock(JobContractInterface::class);

        $event = new JobProcessedEvent($connectionName, $job);

        self::assertInstanceOf(Event::class, $event);
        self::assertEquals($connectionName, $event->getConnectionName());
        self::assertEquals($job, $event->getJob());
    }
}
