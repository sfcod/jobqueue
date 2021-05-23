<?php

namespace SfCod\QueueBundle\Job;

use Exception;
use SfCod\QueueBundle\Base\InteractWithTimeTrait;
use SfCod\QueueBundle\Base\JobInterface;
use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Entity\Job;
use SfCod\QueueBundle\Queue\QueueInterface;

/**
 * Class Job
 *
 * @author Alexey Orlov <aaorlov88@gmail.com>
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Base
 */
class JobContract implements JobContractInterface
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
     * Job resolver
     *
     * @var JobResolverInterface
     */
    protected $resolver;

    /**
     * The database queue instance.
     *
     * @var QueueInterface
     */
    protected $database;

    /**
     * The database job payload.
     *
     * @var Job
     */
    protected $job;

    /**
     * Create a new job instance.
     *
     * @param JobResolverInterface $resolver
     * @param QueueInterface $database
     * @param Job $job
     */
    public function __construct(JobResolverInterface $resolver, QueueInterface $database, Job $job)
    {
        $this->resolver = $resolver;
        $this->database = $database;
        $this->job = $job;
    }

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
     */
    public function delete()
    {
        $this->deleted = true;

        $this->database->deleteReserved($this->job->getQueue(), $this->getJobId());
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

        $this->database->deleteReserved($this->job->getQueue(), $this->getJobId());
        $this->database->release($this, $delay);
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
     * Get the decoded body of the job.
     *
     * @return array
     */
    public function payload(): array
    {
        return $this->job->getPayload();
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
        return $this->connectionName ?? $this->database->getConnectionName();
    }

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue(): ?string
    {
        return $this->job->getQueue();
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int
    {
        return $this->job->getAttempts();
    }

    /**
     * Check if job reserved
     *
     * @return bool
     */
    public function reserved(): bool
    {
        return $this->job->isReserved();
    }

    /**
     * Get reserved at time
     *
     * @return int|null
     */
    public function reservedAt(): ?int
    {
        return $this->job->getReservedAt();
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId(): string
    {
        return $this->job->getId();
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody(): string
    {
        return json_encode($this->job->getPayload());
    }

    /**
     * Resolve job
     *
     * @param string $class
     *
     * @return JobInterface
     */
    protected function resolve(string $class): JobInterface
    {
        return $this->resolver->resolve($class);
    }
}
