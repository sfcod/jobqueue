<?php

namespace SfCod\QueueBundle\Failer;

use Exception;
use Predis\Client;
use Predis\Collection\Iterator\HashKey;
use SfCod\QueueBundle\Base\RandomizeTrait;
use SfCod\QueueBundle\Entity\Job;
use SfCod\QueueBundle\Service\RedisDriver;

/**
 * Redis provider for failed jobs
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class RedisFailedJobProvider implements FailedJobProviderInterface
{
    use RandomizeTrait;

    /**
     * @var RedisDriver
     */
    private $redis;

    /**
     * The database collection.
     *
     * @var string
     */
    private $collection;

    /**
     * Create a new database failed job provider.
     *
     * @param RedisDriver $redis
     * @param string $collection
     */
    public function __construct(RedisDriver $redis, string $collection = 'queue_jobs_failed')
    {
        $this->redis = $redis;
        $this->collection = $collection;
    }

    /**
     * Log a failed job into storage.
     *
     * @param string $connection
     * @param string $queue
     * @param string $payload
     * @param Exception $exception
     *
     * @return int|void|null
     *
     * @throws Exception
     */
    public function log(string $connection, string $queue, string $payload, Exception $exception)
    {
        $this->getClient()->hset($this->collection, $this->getRandomId(), json_encode([
            'connection' => $connection,
            'queue' => $queue,
            'payload' => json_decode($payload, true),
            'exception' => $exception->getMessage(),
            'failed_at' => time(),
        ]));
    }

    /**
     * Get a list of all failed jobs.
     *
     * @return array
     */
    public function all(): array
    {
        $result = [];
        $cursor = new HashKey($this->getClient(), $this->collection);

        foreach ($cursor as $key => $value) {
            $data = json_decode($value, true);

            $result[] = $this->buildJob($key, $data['queue'], $data['payload']);
        }

        return $result;
    }

    /**
     * Get a single failed job.
     *
     * @param string $id
     *
     * @return Job|null
     */
    public function find(string $id): ?Job
    {
        $json = $this->getClient()->hget($this->collection, $id);

        if (!$json) {
            return null;
        }

        $data = json_decode($json, true);

        return $this->buildJob($id, $data['queue'], $data['payload']);
    }

    /**
     * Delete a single failed job from storage.
     *
     * @param string $id
     *
     * @return bool
     */
    public function forget(string $id): bool
    {
        return (bool)$this->getClient()->hdel($this->collection, [$id]);
    }

    /**
     * Flush all of the failed jobs from storage.
     */
    public function flush()
    {
        $this->getClient()->del([$this->collection]);
    }

    /**
     * Get redis client
     *
     * @return Client
     */
    private function getClient(): Client
    {
        return $this->redis->getClient();
    }

    /**
     * Build job from database data
     *
     * @param string $id
     * @param string $queue
     * @param array $payload
     *
     * @return Job
     */
    private function buildJob(string $id, string $queue, array $payload): Job
    {
        $job = new Job();
        $job->setId($id);
        $job->setQueue($queue);
        $job->setAttempts(0);
        $job->setReserved(false);
        $job->setReservedAt(null);
        $job->setPayload($payload);

        return $job;
    }
}
