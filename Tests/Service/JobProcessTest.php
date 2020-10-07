<?php

namespace SfCod\QueueBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Job\JobContractInterface;
use SfCod\QueueBundle\Service\JobProcess;
use SfCod\QueueBundle\Worker\Options;

/**
 * Class JobProcessTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\Service
 */
class JobProcessTest extends TestCase
{
    /**
     * Test get process
     */
    public function testGetProcess()
    {
        $scriptName = uniqid('script_');
        $binPath = __DIR__;
        $binary = 'php';
        $binaryArgs = '';

        $jobProcess = new JobProcess($scriptName, $binPath, $binary, $binaryArgs);

        $jobId = uniqid('id_');
        $jobQueue = uniqid('queue_');

        $job = $this->createMock(JobContractInterface::class);
        $job
            ->expects($this->exactly(2))
            ->method('getJobId')
            ->will($this->returnValue($jobId));
        $job
            ->expects($this->exactly(2))
            ->method('getQueue')
            ->will($this->returnValue($jobQueue));

        $connectionName = uniqid('connection_');

        $job
            ->method('getConnectionName')
            ->willReturn($connectionName);

        $process = $jobProcess->getProcess($job, new Options());

        $command = sprintf("'php' %s job-queue:run-job %s --connection=%s --queue=%s --env=%s --delay=0 --memory=128 --timeout=60 --sleep=3 --maxTries=0 > /dev/null 2>&1 &", $scriptName, $job->getJobId(), $connectionName, $job->getQueue(), getenv('APP_ENV'));

        $this->assertEquals($command, $process->getCommandLine());
    }
}
