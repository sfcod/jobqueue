<?php

namespace SfCod\QueueBundle\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Queue\QueueInterface;
use SfCod\QueueBundle\Service\JobQueue;
use SfCod\QueueBundle\Service\QueueManager;
use SfCod\QueueBundle\Tests\Data\LoadTrait;

/**
 * Class JobQueueTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\Service
 */
class JobQueueTest extends TestCase
{
    use LoadTrait;

    /**
     * Set up test
     */
    protected function setUp(): void
    {
        $this->configure();
    }

    /**
     * Test pushing into queue
     */
    public function testPush()
    {
        $job = uniqid('job_');
        $data = array_rand(range(0, 100), 10);
        $queue = uniqid('queue_');
        $connection = uniqid('connection_');

        $manager = $this->mockManager();
        $manager
            ->expects(self::once())
            ->method('push')
            ->with($job, $data, $queue, $connection)
            ->willReturn(true);

        $jobQueue = $this->mockJobQueue($manager);

        self::assertTrue($jobQueue->push($job, $data, $queue, $connection));
    }

    /**
     * Test push unique job into queue
     */
    public function testPushUnique()
    {
        $job = uniqid('job_');
        $data = array_rand(range(0, 100), 10);
        $queue = uniqid('queue_');
        $connection = uniqid('connection_');

        $connectionMock = $this->createMock(QueueInterface::class);
        $connectionMock
            ->expects(self::once())
            ->method('exists')
            ->with(self::equalTo($job), self::equalTo($data), self::equalTo($queue))
            ->willReturn(false);

        $manager = $this->mockManager();
        $manager
            ->expects(self::once())
            ->method('connection')
            ->with(self::equalTo($connection))
            ->willReturn($connectionMock);
        $manager
            ->expects(self::once())
            ->method('push')
            ->with($job, $data, $queue, $connection)
            ->willReturn(true);

        $jobQueue = $this->mockJobQueue($manager);

        self::assertTrue($jobQueue->pushUnique($job, $data, $queue, $connection));

        $connectionMock = $this->createMock(QueueInterface::class);
        $connectionMock
            ->expects(self::once())
            ->method('exists')
            ->with(self::equalTo($job), self::equalTo($data), self::equalTo($queue))
            ->willReturn(true);

        $manager = $this->mockManager();
        $manager
            ->expects(self::once())
            ->method('connection')
            ->with(self::equalTo($connection))
            ->willReturn($connectionMock);

        $jobQueue = $this->mockJobQueue($manager);

        self::assertNull($jobQueue->pushUnique($job, $data, $queue, $connection));
    }

    /**
     * Test bulk pushing into queue
     */
    public function testBulk()
    {
        $jobs = [];

        for ($i = 0; $i < 10; ++$i) {
            $jobs[] = uniqid('job_' . $i);
        }

        $data = array_rand(range(0, 100), 10);
        $queue = uniqid('queue_');
        $connection = uniqid('connection_');

        $manager = $this->mockManager();
        $manager
            ->expects(self::once())
            ->method('bulk')
            ->with($jobs, $data, $queue, $connection)
            ->willReturn(true);

        $jobQueue = $this->mockJobQueue($manager);

        self::assertTrue($jobQueue->bulk($jobs, $data, $queue, $connection));
    }

    /**
     * Test pushing job with delay
     */
    public function testLater()
    {
        $delay = rand(1, 1000);
        $job = uniqid('job_');
        $data = array_rand(range(0, 100), 10);
        $queue = uniqid('queue_');
        $connection = uniqid('connection_');

        $manager = $this->mockManager();
        $manager
            ->expects(self::once())
            ->method('later')
            ->with($delay, $job, $data, $queue, $connection)
            ->willReturn(true);

        $jobQueue = $this->mockJobQueue($manager);

        self::assertTrue($jobQueue->later($delay, $job, $data, $queue, $connection));
    }

    /**
     * Test push unique job with delay into queue
     */
    public function testLaterUnique()
    {
        $delay = rand(1, 1000);
        $job = uniqid('job_');
        $data = array_rand(range(0, 100), 10);
        $queue = uniqid('queue_');
        $connection = uniqid('connection_');

        $connectionMock = $this->createMock(QueueInterface::class);
        $connectionMock
            ->expects(self::once())
            ->method('exists')
            ->with(self::equalTo($job), self::equalTo($data), self::equalTo($queue))
            ->willReturn(false);

        $manager = $this->mockManager();
        $manager
            ->expects(self::once())
            ->method('connection')
            ->with(self::equalTo($connection))
            ->willReturn($connectionMock);
        $manager
            ->expects(self::once())
            ->method('later')
            ->with($delay, $job, $data, $queue, $connection)
            ->willReturn(true);

        $jobQueue = $this->mockJobQueue($manager);

        self::assertTrue($jobQueue->laterUnique($delay, $job, $data, $queue, $connection));

        $connectionMock = $this->createMock(QueueInterface::class);
        $connectionMock
            ->expects(self::once())
            ->method('exists')
            ->with(self::equalTo($job), self::equalTo($data), self::equalTo($queue))
            ->willReturn(true);

        $manager = $this->mockManager();
        $manager
            ->expects(self::once())
            ->method('connection')
            ->with(self::equalTo($connection))
            ->willReturn($connectionMock);

        $jobQueue = $this->mockJobQueue($manager);

        self::assertNull($jobQueue->laterUnique($delay, $job, $data, $queue, $connection));
    }

    /**
     * Mock job queue
     *
     * @param QueueManager $manager
     *
     * @return JobQueue|MockObject
     */
    private function mockJobQueue(QueueManager $manager): JobQueue
    {
        $jobQueue = $this->getMockBuilder(JobQueue::class)
            ->setConstructorArgs([$manager])
            ->setMethods(null)
            ->getMock();

        return $jobQueue;
    }

    /**
     * Mock manager
     *
     * @return QueueManager|MockObject
     */
    private function mockManager(): QueueManager
    {
        $manager = $this->getMockBuilder(QueueManager::class)
            ->setMethods([
                'push',
                'bulk',
                'later',
                'connection',
            ])
            ->getMock();

        return $manager;
    }
}
