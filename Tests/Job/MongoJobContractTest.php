<?php

namespace SfCod\QueueBundle\Tests\Job;

use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Base\JobInterface;
use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Job\MongoJobContract;
use SfCod\QueueBundle\Queue\QueueInterface;
use Exception;

/**
 * Class MongoJobContractTest
 * @author Virchenko Maksim <muslim1992@gmail.com>
 * @package SfCod\QueueBundle\Tests\Job
 */
class MongoJobContractTest extends TestCase
{
    /**
     * Test job
     */
    public function testJob()
    {
        $job = $this->mockJob();
        $contract = $this->mockMongoJobContract($job);

        $this->assertEquals($job->_id, $contract->getJobId());
        $this->assertEquals($job->attempts, $contract->attempts());
        $this->assertEquals($job->reserved, $contract->reserved());
        $this->assertEquals($job->reserved_at, $contract->reservedAt());
        $this->assertEquals($job->payload, $contract->getRawBody());
    }

    /**
     * Test job payload
     */
    public function testJobPayload()
    {
        $job = $this->mockJob();
        $contract = $this->mockMongoJobContract($job);

        $payload = json_decode($job->payload, true);

        $this->assertEquals($payload, $contract->payload());
        $this->assertEquals($payload['job'], $contract->getName());
        $this->assertEquals($payload['data'], $contract->getData());
        $this->assertEquals($payload['maxTries'], $contract->maxTries());
        $this->assertEquals($payload['timeout'], $contract->timeout());
        $this->assertEquals($payload['timeoutAt'], $contract->timeoutAt());
    }

    /**
     * Test contract
     */
    public function testContract()
    {
        $job = $this->mockJob();
        $contract = $this->mockMongoJobContract($job);

        $this->assertFalse($contract->isDeleted());
        $this->assertFalse($contract->isDeletedOrReleased());

        $contract->delete();

        $this->assertTrue($contract->isDeleted());
        $this->assertTrue($contract->isDeletedOrReleased());

        $this->assertFalse($contract->isReleased());

        $contract->release();

        $this->assertTrue($contract->isReleased());

        $this->assertFalse($contract->hasFailed());

        $contract->markAsFailed();

        $this->assertTrue($contract->hasFailed());
    }

    /**
     * Test contract main actions
     */
    public function testActions()
    {
        $job = $this->mockJob();

        $payload = json_decode($job->payload, true);
        $exception = new Exception(uniqid('message_'));

        $jobInstance = $this->getMockBuilder(JobInterface::class)
            ->setMethods([
                'fire',
                'failed',
            ])
            ->getMock();
        $jobInstance
            ->expects($this->once())
            ->method('fire')
            ->with($this->anything(), $this->equalTo($payload['data']));
        $jobInstance
            ->expects($this->once())
            ->method('failed')
            ->with($this->equalTo($payload['data']), $this->equalTo($exception));

        $contract = $this->mockMongoJobContract($job, $jobInstance);

        $contract->fire();

        $this->assertFalse($contract->hasFailed());

        $contract->failed($exception);

        $this->assertTrue($contract->hasFailed());
    }

    /**
     * Mock job
     *
     * @return object
     */
    private function mockJob()
    {
        $job = [
            '_id' => new \MongoDB\BSON\ObjectID(),
            'attempts' => rand(1, 10),
            'reserved' => (bool)rand(0, 1),
            'reserved_at' => time(),
            'payload' => json_encode([
                'job' => uniqid('job_'),
                'data' => range(1, 10),
                'maxTries' => rand(1, 10),
                'timeout' => rand(1, 1000),
                'timeoutAt' => time() + rand(1, 1000),
            ]),
        ];

        return (object)$job;
    }

    /**
     * Mock mongo job contract
     *
     * @param $job
     * @param JobInterface|null $jobInstance
     *
     * @return MongoJobContract
     */
    private function mockMongoJobContract($jobData, ?JobInterface $jobInstance = null): MongoJobContract
    {
        $queueName = uniqid('queue_name_');
        $payload = json_decode($jobData->payload, true);

        $queue = $this->createMock(QueueInterface::class);

        if (is_null($jobInstance)) {
            $jobInstance = $this->createMock(JobInterface::class);
        }

        $resolver = $this->createMock(JobResolverInterface::class);
        $resolver
            ->expects($this->any())
            ->method('resolve')
            ->with($this->equalTo($payload['job']))
            ->will($this->returnValue($jobInstance));

        $contract = $this->getMockBuilder(MongoJobContract::class)
            ->setConstructorArgs([$resolver, $queue, $jobData, $queueName])
            ->setMethods(null)
            ->getMock();

        return $contract;
    }
}