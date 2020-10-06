<?php

namespace SfCod\QueueBundleTests\Event;

use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Event\JobProcessingEvent;
use SfCod\QueueBundle\Job\JobContractInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class JobProcessingEventTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\Event
 */
class JobProcessingEventTest extends TestCase
{
    /**
     * Test event
     */
    public function testEvent(): void
    {
        $connectionName = uniqid('connection_', true);
        $job = $this->createMock(JobContractInterface::class);

        $event = new JobProcessingEvent($connectionName, $job);

        self::assertInstanceOf(Event::class, $event);
        self::assertEquals($connectionName, $event->getConnectionName());
        self::assertEquals($job, $event->getJob());
    }
}
