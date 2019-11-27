<?php

namespace SfCod\QueueBundle\Tests\JobContract;

use Exception;
use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Base\JobInterface;
use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Entity\Job;
use SfCod\QueueBundle\Job\JobContract;
use SfCod\QueueBundle\Queue\QueueInterface;

/**
 * Class JobContractTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\JobContract
 */
class JobContractTest extends TestCase
{
    /**
     * Test job
     */
    public function testJob()
    {
        $job = $this->mockJob();
        $contract = $this->mockJobContract($job);

        $this->assertEquals($job->getId(), $contract->getJobId());
        $this->assertEquals($job->getAttempts(), $contract->attempts());
        $this->assertEquals($job->isReserved(), $contract->reserved());
        $this->assertEquals($job->getReservedAt(), $contract->reservedAt());
        $this->assertEquals($job->getPayload(), $contract->payload());
    }

    /**
     * Test job payload
     */
    public function testJobPayload()
    {
        $job = $this->mockJob();
        $contract = $this->mockJobContract($job);

        $this->assertEquals($job->getPayload(), $contract->payload());
        $this->assertEquals($job->getPayload()['job'], $contract->getName());
        $this->assertEquals($job->getPayload()['data'], $contract->getData());
        $this->assertEquals($job->getPayload()['maxTries'], $contract->maxTries());
        $this->assertEquals($job->getPayload()['timeout'], $contract->timeout());
        $this->assertEquals($job->getPayload()['timeoutAt'], $contract->timeoutAt());
    }

    /**
     * Test contract
     */
    public function testContract()
    {
        $job = $this->mockJob();
        $contract = $this->mockJobContract($job);

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
            ->with($this->anything(), $this->equalTo($job->getPayload()['data']));
        $jobInstance
            ->expects($this->once())
            ->method('failed')
            ->with($this->equalTo($job->getPayload()['data']), $this->equalTo($exception));

        $contract = $this->mockJobContract($job, $jobInstance);

        $contract->fire();

        $this->assertFalse($contract->hasFailed());

        $contract->failed($exception);

        $this->assertTrue($contract->hasFailed());
    }

    /**
     * Mock job
     *
     * @return Job
     */
    private function mockJob(): Job
    {
        $job = new Job();
        $job->setId(new \MongoDB\BSON\ObjectID());
        $job->setQueue(uniqid('queue_'));
        $job->setAttempts(rand(1, 10));
        $job->setReserved((bool)rand(0, 1));
        $job->setReservedAt(time());
        $job->setPayload([
            'job' => uniqid('job_'),
            'data' => range(1, 10),
            'maxTries' => rand(1, 10),
            'timeout' => rand(1, 1000),
            'timeoutAt' => time() + rand(1, 1000),
        ]);

        return $job;
    }

    /**
     * Mock mongo job contract
     *
     * @param Job $job
     * @param JobInterface|null $jobInstance
     *
     * @return JobContract
     */
    private function mockJobContract(Job $jobData, ?JobInterface $jobInstance = null): JobContract
    {
        $queueName = uniqid('queue_name_');
        $queue = $this->createMock(QueueInterface::class);

        if (is_null($jobInstance)) {
            $jobInstance = $this->createMock(JobInterface::class);
        }

        $resolver = $this->createMock(JobResolverInterface::class);
        $resolver
            ->expects($this->any())
            ->method('resolve')
            ->with($this->equalTo($jobData->getPayload()['job']))
            ->will($this->returnValue($jobInstance));

        $contract = $this->getMockBuilder(JobContract::class)
            ->setConstructorArgs([$resolver, $queue, $jobData, $queueName])
            ->setMethods(null)
            ->getMock();

        return $contract;
    }
}
