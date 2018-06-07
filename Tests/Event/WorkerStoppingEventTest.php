<?php

namespace SfCod\QueueBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Event\WorkerStoppingEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class WorkerStoppingEventTest
 * @author Virchenko Maksim <muslim1992@gmail.com>
 * @package SfCod\QueueBundle\Tests\Event
 */
class WorkerStoppingEventTest extends TestCase
{
    public function testEvent()
    {
        $event = new WorkerStoppingEvent();

        $this->assertInstanceOf(Event::class, $event);
    }
}