<?php

namespace SfCod\QueueBundle\Service;

use SfCod\QueueBundle\Base\JobQueueInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * JobQueue service
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 * @author Orlov Alexey <aaorlov88@gmail.com>
 */
class JobQueue implements JobQueueInterface
{
    /**
     * QueueManager instance
     *
     * @var QueueManager
     */
    protected $manager;

    /**
     * JobQueue constructor.
     *
     * @param ContainerInterface $container
     * @param array $connections
     *
     * @internal param array $config
     */
    public function __construct(QueueManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Push new job to queue
     *
     * @author Virchenko Maksim <muslim1992@gmail.com>
     *
     * @param string $job
     * @param array $data
     * @param string $queue
     * @param string $connection
     *
     * @return mixed
     */
    public function push(string $job, array $data = [], string $queue = 'default', string $connection = 'default')
    {
        return $this->manager->push($job, $data, $queue, $connection);
    }

    /**
     * Push new job to queue if this job is not exist
     *
     * @author Virchenko Maksim <muslim1992@gmail.com>
     *
     * @param string $job
     * @param array $data
     * @param string $queue
     * @param string $connection
     *
     * @return mixed
     */
    public function pushUnique(string $job, array $data = [], string $queue = 'default', string $connection = 'default')
    {
        if (false === $this->manager->connection($connection)->exists($job, $data, $queue)) {
            return $this->manager->push($job, $data, $queue, $connection);
        }

        return null;
    }

    /**
     * Push a new an array of jobs onto the queue.
     *
     * @param array $jobs
     * @param mixed $data
     * @param string $queue
     * @param string $connection
     *
     * @return mixed
     */
    public function bulk(array $jobs, array $data = [], string $queue = 'default', string $connection = 'default')
    {
        return $this->manager->bulk($jobs, $data, $queue, $connection);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param \DateTime|int $delay
     * @param string $job
     * @param mixed $data
     * @param string $queue
     * @param string $connection
     *
     * @return mixed
     */
    public function later(int $delay, string $job, array $data = [], string $queue = 'default', string $connection = 'default')
    {
        return $this->manager->later($delay, $job, $data, $queue, $connection);
    }

    /**
     * Push a new job into the queue after a delay if job does not exist.
     *
     * @param \DateTime|int $delay
     * @param string $job
     * @param mixed $data
     * @param string $queue
     * @param string $connection
     *
     * @return mixed
     */
    public function laterUnique(int $delay, string $job, array $data = [], string $queue = 'default', string $connection = 'default')
    {
        if (false === $this->manager->connection($connection)->exists($job, $data, $queue)) {
            return $this->manager->later($delay, $job, $data, $queue, $connection);
        }

        return null;
    }
}
