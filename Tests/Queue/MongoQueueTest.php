<?php

namespace SfCod\QueueBundle\Tests\Queue;

use MongoDB\Database;
use Helmich\MongoMock\MockDatabase;
use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Base\MongoDriverInterface;
use SfCod\QueueBundle\Queue\MongoQueue;

/**
 * Class MongoQueueTest
 * @author Virchenko Maksim <muslim1992@gmail.com>
 * @package SfCod\QueueBundle\Tests\Queue
 */
class MongoQueueTest extends TestCase
{
    /**
     * Test pushing into database
     */
    public function testPush()
    {
        $collection = uniqid('collection_');
        $jobName = uniqid('job_');
        $data = range(1, 10);

        $database = new MockDatabase();

        $mongoQueue = $this->mockMongoQueue($database, $collection);

        $mongoQueue->push($jobName, $data);

        $this->assertEquals(1, $database->selectCollection($collection)->count());

        $job = $database->selectCollection($collection)->findOne();

        $payload = json_decode($job->payload, true);
        $this->assertEquals($jobName, $payload['job']);
        $this->assertEquals($data, $payload['data']);
    }

    /**
     * Test pop from queue
     */
    public function testPop()
    {
        $collection = uniqid('collection_');
        $jobName = uniqid('job_');
        $data = range(1, 10);

        $database = new MockDatabase();

        $mongoQueue = $this->mockMongoQueue($database, $collection);

        $mongoQueue->push($jobName, $data);

        $job = $mongoQueue->pop();
        $this->assertEquals($jobName, $job->getName());
        $this->assertEquals($data, $job->payload()['data']);
    }

    /**
     * Test if job exists
     */
    public function testExists()
    {
        $collection = uniqid('collection_');
        $jobName = uniqid('job_');
        $data = range(1, 10);

        $database = new MockDatabase();

        $mongoQueue = $this->mockMongoQueue($database, $collection);

        $mongoQueue->push($jobName, $data);

        $this->assertTrue($mongoQueue->exists($jobName, $data));
    }

    public function testPushRaw()
    {
        // @TODO
    }

    /**
     * Mock mongo queue
     *
     * @param Database $database
     * @param string $collection
     *
     * @return MongoQueue
     */
    private function mockMongoQueue(Database $database, string $collection): MongoQueue
    {
        $jobResolver = $this->createMock(JobResolverInterface::class);
        $mongo = $this->createMock(MongoDriverInterface::class);
        $mongo
            ->expects($this->any())
            ->method('getDatabase')
            ->will($this->returnValue($database));

        $mongoQueue = new MongoQueue($jobResolver, $mongo, $collection);

        return $mongoQueue;
    }
}