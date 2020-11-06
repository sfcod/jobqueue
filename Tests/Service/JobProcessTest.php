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

        $jobProcess = new JobProcess($scriptName, $binPath);

        $jobId = uniqid('id_');
        $jobQueue = uniqid('queue_');

        $job = $this->createMock(JobContractInterface::class);
        $job
            ->expects(self::exactly(2))
            ->method('getJobId')
            ->willReturn($jobId);
        $job
            ->expects(self::exactly(2))
            ->method('getQueue')
            ->willReturn($jobQueue);

        $connectionName = uniqid('connection_');

        $job
            ->method('getConnectionName')
            ->willReturn($connectionName);

        $process = $jobProcess->getProcess($job, new Options());

        $command = sprintf('%s %s job-queue:run-job %s --connection=%s --queue=%s --env=%s --delay=0 --memory=128 --timeout=60 --sleep=3 --maxTries=0 > /dev/null 2>&1 &', 'php', $binPath . DIRECTORY_SEPARATOR . $scriptName, $job->getJobId(), $connectionName, $job->getQueue(), 'prod');

        self::assertEquals($command, $process->getCommandLine());
    }
}
