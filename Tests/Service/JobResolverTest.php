<?php

namespace SfCod\QueueBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Base\JobInterface;
use SfCod\QueueBundle\Service\JobResolver;

/**
 * Class JobResolverTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\Service
 */
class JobResolverTest extends TestCase
{
    /**
     * Test resolve job by name
     */
    public function testResolve()
    {
        $jobName = uniqid('job_');
        $jobClass = $this->createMock(JobInterface::class);

        $resolver = new JobResolver();
        $resolver->addJob($jobName, $jobClass);

        self::assertEquals($jobClass, $resolver->resolve($jobName));
    }
}
