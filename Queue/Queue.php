<?php

namespace SfCod\QueueBundle\Queue;

use SfCod\QueueBundle\Base\InteractWithTimeTrait;
use SfCod\QueueBundle\Exception\InvalidPayloadException;

/**
 * Class Queue
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Queue
 */
abstract class Queue implements QueueInterface
{
    use InteractWithTimeTrait;

    /**
     * The connection name for the queue.
     *
     * @var string
     */
    protected $connectionName;

    /**
     * Push a new job onto the queue.
     *
     * @param string $queue
     * @param string $job
     * @param array $data
     *
     * @return mixed
     */
    public function pushOn(string $queue, string $job, array $data = [])
    {
        return $this->push($job, $data, $queue);
    }

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
    public function laterOn(string $queue, $delay, string $job, array $data = [])
    {
        return $this->later($delay, $job, $data, $queue);
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param array $jobs
     * @param array $data
     * @param string|null $queue
     */
    public function bulk(array $jobs, array $data = [], ?string $queue = null)
    {
        foreach ($jobs as $job) {
            $this->push($job, $data, $queue);
        }
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param string $job
     * @param mixed $data
     *
     * @return string
     *
     * @throws InvalidPayloadException
     */
    protected function createPayload(string $job, array $data = [])
    {
        $payload = json_encode($this->createPayloadArray($job, $data));

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidPayloadException('Unable to JSON encode payload. Error code: ' . json_last_error());
        }

        return $payload;
    }

    /**
     * Create a payload array from the given job and data.
     *
     * @param string $job
     * @param array $data
     *
     * @return array
     */
    protected function createPayloadArray(string $job, array $data = []): array
    {
        return [
            'displayName' => explode('@', $job)[0],
            'job' => $job,
            'maxTries' => null,
            'timeout' => null,
            'data' => $data,
        ];
    }

    /**
     * Get the connection name for the queue.
     *
     * @return string
     */
    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    /**
     * Set the connection name for the queue.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setConnectionName(string $name): QueueInterface
    {
        $this->connectionName = $name;

        return $this;
    }
}
