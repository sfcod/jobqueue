<?php

namespace SfCod\QueueBundle\Tests\Queue;

use Helmich\MongoMock\MockDatabase;
use MongoDB\Database;
use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Base\MongoDriverInterface;
use SfCod\QueueBundle\Queue\MongoQueue;

/**
 * Class MongoQueueTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
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

    /**
     * Test pushing into database
     */
    public function testPushOn()
    {
        $collection = uniqid('collection_');
        $jobName = uniqid('job_');
        $data = range(1, 10);

        $database = new MockDatabase();

        $mongoQueue = $this->mockMongoQueue($database, $collection);

        $mongoQueue->pushOn('default', $jobName, $data);

        $this->assertEquals(1, $database->selectCollection($collection)->count());

        $job = $database->selectCollection($collection)->findOne();

        $payload = json_decode($job->payload, true);
        $this->assertEquals($jobName, $payload['job']);
        $this->assertEquals($data, $payload['data']);
    }

    /**
     * Test pushing into database
     */
    public function testPushRaw()
    {
        $collection = uniqid('collection_');
        $jobName = uniqid('job_');
        $data = range(1, 10);

        $database = new MockDatabase();

        $mongoQueue = $this->mockMongoQueue($database, $collection);

        $mongoQueue->pushRaw(json_encode(['job' => $jobName, 'data' => $data]));

        $this->assertEquals(1, $database->selectCollection($collection)->count());

        $job = $database->selectCollection($collection)->findOne();

        $payload = json_decode($job->payload, true);
        $this->assertEquals($jobName, $payload['job']);
        $this->assertEquals($data, $payload['data']);
    }

    /**
     * Test pushing job for later
     */
    public function testLater()
    {
        $collection = uniqid('collection_');
        $jobName = uniqid('job_');
        $data = range(1, 10);
        $delay = rand(60, 3600);

        $database = new MockDatabase();

        $mongoQueue = $this->mockMongoQueue($database, $collection);

        $mongoQueue->later($delay, $jobName, $data);

        $job = $database->selectCollection($collection)->findOne();

        $payload = json_decode($job->payload, true);
        $this->assertEquals($jobName, $payload['job']);
        $this->assertEquals($data, $payload['data']);

        $this->assertGreaterThan(time() + $delay - 10, $job->available_at);
    }

    /**
     * Test pushing bulk
     */
    public function testBulk()
    {
        $collection = uniqid('collection_');
        $jobName = uniqid('job_');
        $data = range(1, 10);
        $delay = rand(60, 3600);

        $database = new MockDatabase();

        $mongoQueue = $this->mockMongoQueue($database, $collection);

        for ($i = 0; $i < 10; ++$i) {
            $jobs[] = $jobName . $i;
        }

        $mongoQueue->bulk($jobs, $data);

        $count = $database->selectCollection($collection)->count();

        $this->assertEquals(10, $count);
    }

    public function testRelease()
    {
        //@TODO
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
