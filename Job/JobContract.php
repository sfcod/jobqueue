<?php

namespace SfCod\QueueBundle\Job;

use Exception;
use SfCod\QueueBundle\Base\InteractWithTimeTrait;
use SfCod\QueueBundle\Base\JobInterface;

/**
 * Class Job
 *
 * @author Alexey Orlov <aaorlov88@gmail.com>
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Base
 */
abstract class JobContract implements JobContractInterface
{
    use InteractWithTimeTrait;

    /**
     * The job handler instance.
     *
     * @var JobInterface
     */
    protected $instance;

    /**
     * Indicates if the job has been deleted.
     *
     * @var bool
     */
    protected $deleted = false;

    /**
     * Indicates if the job has been released.
     *
     * @var bool
     */
    protected $released = false;

    /**
     * Indicates if the job has failed.
     *
     * @var bool
     */
    protected $failed = false;

    /**
     * The name of the connection the job belongs to.
     *
     * @var string
     */
    protected $connectionName;

    /**
     * The name of the queue the job belongs to.
     *
     * @var string
     */
    protected $queue;

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire()
    {
        $handler = $this->resolve($this->getName());

        $this->instance = $handler->fire($this, $this->getData());
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        $this->deleted = true;
    }

    /**
     * Determine if the job has been deleted.
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * Release the job back into the queue.
     *
     * @param int $delay
     *
     * @return void
     */
    public function release(int $delay = 0)
    {
        $this->released = true;
    }

    /**
     * Determine if the job was released back into the queue.
     *
     * @return bool
     */
    public function isReleased(): bool
    {
        return $this->released;
    }

    /**
     * Determine if the job has been deleted or released.
     *
     * @return bool
     */
    public function isDeletedOrReleased(): bool
    {
        return $this->isDeleted() || $this->isReleased();
    }

    /**
     * Determine if the job has been marked as a failure.
     *
     * @return bool
     */
    public function hasFailed(): bool
    {
        return $this->failed;
    }

    /**
     * Mark the job as "failed".
     *
     * @return void
     */
    public function markAsFailed()
    {
        $this->failed = true;
    }

    /**
     * Process an exception that caused the job to fail.
     *
     * @param Exception $e
     *
     * @return void
     */
    public function failed($e)
    {
        $this->markAsFailed();

        if (method_exists($this->instance = $this->resolve($this->getName()), 'failed')) {
            $this->instance->failed($this->getData(), $e);
        }
    }

    /**
     * Get the decoded body of the job.
     *
     * @return array
     */
    public function payload(): array
    {
        return json_decode($this->getRawBody(), true);
    }

    /**
     * Get the number of times to attempt a job.
     *
     * @return int|null
     */
    public function maxTries(): ?int
    {
        return $this->payload()['maxTries'] ?? null;
    }

    /**
     * Get the number of seconds the job can run.
     *
     * @return int|null
     */
    public function timeout(): ?int
    {
        return $this->payload()['timeout'] ?? null;
    }

    /**
     * Get the timestamp indicating when the job should timeout.
     *
     * @return int|null
     */
    public function timeoutAt(): ?int
    {
        return $this->payload()['timeoutAt'] ?? null;
    }

    /**
     * Get the name of the queued job class.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->payload()['job'];
    }

    /**
     * Get data of queued job.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->payload()['data'];
    }

    /**
     * Get the name of the connection the job belongs to.
     *
     * @return string
     */
    public function getConnectionName(): ?string
    {
        return $this->connectionName;
    }

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue(): ?string
    {
        return $this->queue;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    abstract public function getJobId(): string;

    /**
     * Get the raw body of the job.
     *
     * @return string
     */
    abstract public function getRawBody(): string;

    /**
     * Resolve the given class
     *
     * @param string $class
     *
     * @return JobInterface
     */
    abstract protected function resolve(string $class): JobInterface;
}
