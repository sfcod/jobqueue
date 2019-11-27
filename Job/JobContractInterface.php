<?php

namespace SfCod\QueueBundle\Job;

/**
 * Interface JobContract
 *
 * @package SfCod\QueueBundle\Base
 */
interface JobContractInterface
{
    /**
     * Get is job reserved
     *
     * @return bool
     */
    public function reserved(): bool;

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId(): string;

    /**
     * Get the decoded body of the job.
     *
     * @return array
     */
    public function payload();

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire();

    /**
     * Release the job back into the queue.
     *
     * @param int $delay
     *
     * @return mixed
     */
    public function release(int $delay = 0);

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete();

    /**
     * Determine if the job has been deleted.
     *
     * @return bool
     */
    public function isDeleted(): bool;

    /**
     * Determine if the job has been deleted or released.
     *
     * @return bool
     */
    public function isDeletedOrReleased(): bool;

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int;

    /**
     * Process an exception that caused the job to fail.
     *
     * @param \Throwable $e
     *
     * @return void
     */
    public function failed($e);

    /**
     * Get the number of times to attempt a job.
     *
     * @return int|null
     */
    public function maxTries(): ?int;

    /**
     * Get the number of seconds the job can run.
     *
     * @return int|null
     */
    public function timeout(): ?int;

    /**
     * Get the timestamp indicating when the job should timeout.
     *
     * @return int|null
     */
    public function timeoutAt(): ?int;

    /**
     * Get the name of the queued job class.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the name of the connection the job belongs to.
     *
     * @return string
     */
    public function getConnectionName(): ?string;

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue(): ?string;

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody(): string;
}
