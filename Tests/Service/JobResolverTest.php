<?php

namespace SfCod\QueueBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Base\JobInterface;
use SfCod\QueueBundle\Service\JobResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo($jobName))
            ->will($this->returnValue($jobClass));

        $resolver = $this->mockResolver($container);

        $this->assertEquals($jobClass, $resolver->resolve($jobName));
    }

    /**
     * Mock resolver
     *
     * @param ContainerInterface $container
     *
     * @return JobResolver
     */
    private function mockResolver(ContainerInterface $container): JobResolver
    {
        $resolver = $this->getMockBuilder(JobResolver::class)
            ->setConstructorArgs([$container])
            ->setMethods(null)
            ->getMock();

        return $resolver;
    }
}
