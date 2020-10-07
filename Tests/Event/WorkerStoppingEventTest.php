<?php

namespace SfCod\QueueBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Event\WorkerStoppingEvent;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class WorkerStoppingEventTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\Event
 */
class WorkerStoppingEventTest extends TestCase
{
    /**
     * Test event
     */
    public function testEvent()
    {
        $event = new WorkerStoppingEvent();

        self::assertInstanceOf(Event::class, $event);
    }
}
