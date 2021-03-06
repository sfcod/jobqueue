<?php

namespace SfCod\QueueBundle\Tests\Queue;

use Helmich\MongoMock\MockDatabase;
use MongoDB\Database;
use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Entity\Job;
use SfCod\QueueBundle\Job\JobContract;
use SfCod\QueueBundle\Job\JobContractInterface;
use SfCod\QueueBundle\Queue\MongoQueue;
use SfCod\QueueBundle\Service\MongoDriver;

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

        self::assertEquals(1, $database->selectCollection($collection)->count());

        $job = $database->selectCollection($collection)->findOne();

        $payload = json_decode($job->payload, true);
        self::assertEquals($jobName, $payload['job']);
        self::assertEquals($data, $payload['data']);
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
        self::assertEquals($jobName, $job->getName());
        self::assertEquals($data, $job->payload()['data']);
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

        self::assertTrue($mongoQueue->exists($jobName, $data));
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

        self::assertEquals(1, $database->selectCollection($collection)->count());

        $job = $database->selectCollection($collection)->findOne();

        $payload = json_decode($job->payload, true);
        self::assertEquals($jobName, $payload['job']);
        self::assertEquals($data, $payload['data']);
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

        $count = $database->selectCollection($collection)->count();
        self::assertEquals(1, $count);

        $job = $database->selectCollection($collection)->findOne();

        $payload = json_decode($job->payload, true);
        self::assertEquals($jobName, $payload['job']);
        self::assertEquals($data, $payload['data']);
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
        self::assertEquals($jobName, $payload['job']);
        self::assertEquals($data, $payload['data']);

        self::assertGreaterThan(time() + $delay - 10, $job->available_at);
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

        self::assertEquals(10, $count);
    }

    /**
     * Test release
     */
    public function testRelease()
    {
        $collection = uniqid('collection_');
        $jobName = uniqid('job_');
        $data = range(1, 10);

        $database = new MockDatabase();

        $mongoQueue = $this->mockMongoQueue($database, $collection);

        $mongoQueue->push($jobName, $data);

        $job = $database->selectCollection($collection)->findOne();

        $database->selectCollection($collection)->deleteMany([]);

        $jobToRelease = new Job();
        $jobToRelease->setId($job->_id);
        $jobToRelease->setQueue($job->queue);
        $jobToRelease->setAttempts($job->attempts);

        $jobResolver = $this->createMock(JobResolverInterface::class);

        $jobContract = new JobContract($jobResolver, $mongoQueue, $jobToRelease);

        $mongoQueue->release($jobContract, 0);

        $count = $database->selectCollection($collection)->count();

        self::assertEquals(1, $count);
    }

    /**
     * Test getting job by id
     */
    public function testGetJobById()
    {
        $collection = uniqid('collection_');
        $jobName = uniqid('job_');
        $data = range(1, 10);

        $database = new MockDatabase();

        $mongoQueue = $this->mockMongoQueue($database, $collection);

        $mongoQueue->push($jobName, $data);

        $job = $database->selectCollection($collection)->findOne();

        $jobContract = $mongoQueue->getJobById('default', $job->_id);

        self::assertInstanceOf(JobContractInterface::class, $jobContract);
        self::assertEquals($jobContract->getName(), $jobName);
    }

    /**
     * Test deleting reserved
     */
    public function testDeleteReserved()
    {
        $collection = uniqid('collection_');
        $jobName = uniqid('job_');
        $data = range(1, 10);

        $database = new MockDatabase();

        $mongoQueue = $this->mockMongoQueue($database, $collection);

        $mongoQueue->push($jobName, $data);

        $count = $database->selectCollection($collection)->count();

        self::assertEquals(1, $count);

        $job = $database->selectCollection($collection)->findOne();
        $result = $mongoQueue->deleteReserved($job->queue, $job->_id);

        self::assertTrue($result);

        $count = $database->selectCollection($collection)->count();

        self::assertEquals(0, $count);
    }

    /**
     * Test expire queue
     */
    public function testExpire()
    {
        $collection = uniqid('collection_');
        $expire = rand(1, 99999);

        $database = new MockDatabase();

        $mongoQueue = $this->mockMongoQueue($database, $collection);

        $mongoQueue->setExpire($expire);

        self::assertEquals($expire, $mongoQueue->getExpire());
    }

    /**
     * Test queue's size
     */
    public function testSize()
    {
        $collection = uniqid('collection_');
        $jobName = uniqid('job_');
        $data = range(1, 10);

        $database = new MockDatabase();

        $mongoQueue = $this->mockMongoQueue($database, $collection);

        for ($i = 0; $i < 10; ++$i) {
            $mongoQueue->push($jobName, $data);
        }

        $job = $database->selectCollection($collection)->findOne();

        $count = $database->selectCollection($collection)->count();

        self::assertEquals($count, $mongoQueue->size());
        self::assertEquals($count, $mongoQueue->size($job->queue));
    }

    /**
     * Test can run job
     */
    public function testCanRunJob()
    {
        $collection = uniqid('collection_');
        $jobName = uniqid('job_');
        $data = range(1, 10);

        $database = new MockDatabase();

        $mongoQueue = $this->mockMongoQueue($database, $collection);

        $mongoQueue->push($jobName, $data);

        $job = $database->selectCollection($collection)->findOne();

        /** @var JobContractInterface $jobContract */
        $jobContract = $mongoQueue->getJobById('default', $job->_id);

        $canRun = $mongoQueue->canRunJob($jobContract);

        self::assertTrue($canRun);
    }

    /**
     * Test mark job as reserved
     */
    public function testMarkJobAsReserved()
    {
        $collection = uniqid('collection_');
        $jobName = uniqid('job_');
        $data = range(1, 10);

        $database = new MockDatabase();

        $mongoQueue = $this->mockMongoQueue($database, $collection);

        $mongoQueue->push($jobName, $data);

        $job = $database->selectCollection($collection)->findOne();
        $attempts = $job->attempts;

        /** @var JobContractInterface $jobContract */
        $jobContract = $mongoQueue->getJobById('default', $job->_id);

        $mongoQueue->markJobAsReserved($jobContract);

        $reservedJob = $database->selectCollection($collection)->findOne();

        self::assertTrue((bool)$reservedJob->reserved);
        self::assertGreaterThan($attempts, $reservedJob->attempts);
        self::assertNotNull($reservedJob->reserved_at);
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
        $mongo = $this->createMock(MongoDriver::class);
        $mongo
            ->expects(self::any())
            ->method('getDatabase')
            ->willReturn($database);

        $mongoQueue = new MongoQueue($jobResolver, $mongo, $collection);

        return $mongoQueue;
    }
}
