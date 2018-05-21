<?php

namespace SfCod\QueueBundle\Failer;

use Exception;

/**
 * Interface FailedJobProviderInterface
 *
 * @package SfCod\QueueBundle\Base
 */
interface FailedJobProviderInterface
{
    /**
     * Log a failed job into storage.
     *
     * @param string $connection
     * @param string $queue
     * @param string $payload
     * @param Exception $exception
     *
     * @return int|null
     */
    public function log(string $connection, string $queue, string $payload, Exception $exception);

    /**
     * Get a list of all of the failed jobs.
     *
     * @return array
     */
    public function all();

    /**
     * Get a single failed job.
     *
     * @param mixed $id
     *
     * @return object|null
     */
    public function find($id);

    /**
     * Delete a single failed job from storage.
     *
     * @param mixed $id
     *
     * @return bool
     */
    public function forget($id);

    /**
     * Flush all of the failed jobs from storage.
     *
     * @return void
     */
    public function flush();
}
