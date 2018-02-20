<?php

namespace SfCod\QueueBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Service\JobQueue;
use SfCod\QueueBundle\Tests\Data\LoadTrait;
use SfCod\SocketIoBundle\Service\Broadcast;

class JobQueueTest extends TestCase
{
    use LoadTrait;

    /**
     * @var Broadcast
     */
    private $jobQueue;

    protected function setUp()
    {
        $this->configure();

        $this->jobQueue = $this->container->get(JobQueue::class);
    }

    public function testGetQueueManager()
    {
        $this->assertTrue(is_a($this->jobQueue->getQueueManager(), \Illuminate\Queue\QueueManager::class));
    }
}
