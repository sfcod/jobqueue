<?php

namespace SfCod\QueueBundle\Queue;

use SfCod\QueueBundle\Job\JobContractInterface;

/**
 * Interface QueueInterface
 *
 * @package SfCod\QueueBundle\Queue
 */
interface QueueInterface
{
    /**
     * Get the size of the queue.
     *
     * @param string $queue
     *
     * @return int
     */
    public function size(?string $queue = null): int;

    /**
     * Push a new job onto the queue.
     *
     * @param string $job
     * @param array $data
     * @param string $queue
     *
     * @return mixed
     */
    public function push(string $job, array $data = [], ?string $queue = null);

    /**
     * Push a new job onto the queue.
     *
     * @param string $queue
     * @param string $job
     * @param array $data
     *
     * @return mixed
     */
    public function pushOn(string $queue, string $job, array $data = []);

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string $queue
     * @param array $options
     *
     * @return mixed
     */
    public function pushRaw(string $payload, ?string $queue = null, array $options = []);

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param \DateTimeInterface|\DateInterval|int $delay
     * @param string $job
     * @param array $data
     * @param string $queue
     *
     * @return mixed
     */
    public function later($delay, string $job, array $data = [], ?string $queue = null);

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param string $queue
     * @param \DateTimeInterface|\DateInterval|int $delay
     * @param string $job
     * @param array $data
     *
     * @return mixed
     */
    public function laterOn(string $queue, $delay, string $job, array $data = []);

    /**
     * Push an array of jobs onto the queue.
     *
     * @param array $jobs
     * @param array $data
     * @param string $queue
     *
     * @return mixed
     */
    public function bulk(array $jobs, array $data = [], ?string $queue = null);

    /**
     * Pop the next job off of the queue.
     *
     * @param string $queue
     *
     * @return JobContractInterface|null
     */
    public function pop(?string $queue = null): ?JobContractInterface;

    /**
     * If job exists
     *
     * @param string $job
     * @param array $data
     * @param string|null $queue
     *
     * @return bool
     */
    public function exists(string $job, array $data = [], ?string $queue = null): bool;

    /**
     * Get the connection name for the queue.
     *
     * @return string
     */
    public function getConnectionName(): string;

    /**
     * Set the connection name for the queue.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setConnectionName(string $name): QueueInterface;

    /**
     * Check if job can be runned.
     *
     * @param JobContract $job
     *
     * @return bool
     */
    public function canRunJob(JobContractInterface $job): bool;

    /**
     * Get job by id
     *
     * @param $id
     *
     * @return null|JobContractInterface
     */
    public function getJobById($id): ?JobContractInterface;

    /**
     * Mark job as reserved
     *
     * @param JobContractInterface $job
     *
     * @return mixed
     */
    public function markJobAsReserved(JobContractInterface $job);

    /**
     * Delete reserved job
     *
     * @param string $queue
     * @param $id
     *
     * @return int
     */
    public function deleteReserved(string $queue, $id): int;
}
