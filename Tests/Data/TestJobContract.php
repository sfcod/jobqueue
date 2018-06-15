<?php

namespace SfCod\QueueBundle\Tests\Data;

use SfCod\QueueBundle\Base\JobInterface;
use SfCod\QueueBundle\Job\JobContract;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test job
 *
 * Class TestJob
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests
 */
class TestJobContract extends JobContract
{
    /**
     * @var ContainerInterface
     */
    private $_container;

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId(): string
    {
        return uniqid();
    }

    /**
     * TestJob constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->_container = $container;
    }

    /**
     * Get container instance
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->_container;
    }

    /**
     * Delete job
     *
     * @throws SuccessJobException
     */
    public function delete()
    {
        throw new SuccessJobException('Job was deleted.');
    }

    /**
     * Get the raw body of the job.
     *
     * @return string
     */
    public function getRawBody(): string
    {
        return '';
    }

    /**
     * Resolve the given class
     *
     * @param string $class
     *
     * @return JobInterface
     */
    protected function resolve(string $class): JobInterface
    {
        return $this->_container->get($class);
    }

    /**
     * Get is job reserved
     *
     * @return bool
     */
    public function reserved(): bool
    {
        return false;
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int
    {
        return 1;
    }
}
