<?php

namespace SfCod\QueueBundle\Base;

/**
 * Job queue interface
 *
 * @author Orlov Alexey <aaorlov88@gmail.com>
 */
interface JobQueueInterface
{
    /**
     * Push new job to queue
     *
     * @param string $job
     * @param array $data
     * @param string $queue
     * @param string $connection
     *
     * @return mixed
     */
    public function push(string $job, array $data = [], string $queue = 'default', string $connection = 'default');

    /**
     * Push new job to queue if this job is not exist
     *
     * @param string $job
     * @param array $data
     * @param string $queue
     * @param string $connection
     *
     * @return mixed
     */
    public function pushUnique(string $job, array $data = [], string $queue = 'default', string $connection = 'default');

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
    public function bulk(array $jobs, array $data = [], string $queue = 'default', string $connection = 'default');

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
    public function later($delay, string $job, array $data = [], string $queue = 'default', string $connection = 'default');

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
    public function laterUnique($delay, string $job, array $data = [], string $queue = 'default', string $connection = 'default');
}
