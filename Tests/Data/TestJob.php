<?php

namespace SfCod\QueueBundle\Tests\Data;

use Illuminate\Queue\Jobs\Job;
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
class TestJob extends Job
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
    public function getJobId()
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
     * @return \Illuminate\Container\Container|ContainerInterface
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
    public function getRawBody()
    {
        return '';
    }
}
