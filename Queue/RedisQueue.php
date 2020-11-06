<?php

namespace SfCod\QueueBundle\Queue;

use DateInterval;
use DateTime;
use Predis\Client;
use Predis\Collection\Iterator\HashKey;
use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Base\RandomizeTrait;
use SfCod\QueueBundle\Entity\Job;
use SfCod\QueueBundle\Job\JobContract;
use SfCod\QueueBundle\Job\JobContractInterface;
use SfCod\QueueBundle\Service\RedisDriver;

/**
 * Class RedisQueue
 * @author Virchenko Maksim <muslim1992@gmail.com>
 * @package SfCod\QueueBundle\Queue
 */
class RedisQueue extends Queue
{
    use RandomizeTrait;

    /**
     * Job resolver
     *
     * @var JobResolverInterface
     */
    private $resolver;

    /**
     * @var RedisDriver
     */
    private $redis;

    /**
     * The collection that holds the jobs.
     *
     * @var string
     */
    private $collection;

    /**
     * The name of the default queue.
     *
     * @var string
     */
    private $queue = 'default';

    /**
     * The expiration time of a job.
     *
     * @var int|null
     */
    private $expire = 60;

    /**
     * @var int
     */
    private $limit = 15;

    /**
     * Create a new redis queue instance.
     *
     * @param JobResolverInterface $resolver
     * @param RedisDriver $redis
     * @param string $collection
     * @param string $queue
     * @param int $expire
     * @param int $limit
     */
    public function __construct(
        JobResolverInterface $resolver,
        RedisDriver $redis,
        string $collection = 'queue_jobs',
        string $queue = 'default',
        int $expire = 60,
        int $limit = 15
    )
    {
        $this->resolver = $resolver;
        $this->redis = $redis;
        $this->collection = $collection;
        $this->expire = $expire;
        $this->queue = $queue;
        $this->limit = $limit;
    }

    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
     *
     * @return int
     */
    public function size(?string $queue = null): int
    {
        return (int)$this->getClient()->zcount($this->buildKey($queue), '-inf', '+inf');
    }

    /**
     * Push a new job onto the queue.
     *
     * @param string $job
     * @param mixed $data
     * @param string|null $queue
     *
     * @return mixed
     * @throws \Exception
     */
    public function push(string $job, array $data = [], ?string $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string|null $queue
     * @param array $options
     *
     * @return mixed
     * @throws \Exception
     */
    public function pushRaw(string $payload, ?string $queue = null, array $options = [])
    {
        return $this->pushToDatabase(0, $queue, $payload);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param DateInterval|int $delay
     * @param string $job
     * @param array $data
     * @param string|null $queue
     *
     * @return mixed
     * @throws \Exception
     */
    public function later($delay, string $job, array $data = [], ?string $queue = null)
    {
        return $this->pushToDatabase($delay, $queue, $this->createPayload($job, $data));
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     *
     * @return null|JobContractInterface
     */
    public function pop(?string $queue = null): ?JobContractInterface
    {
        $id = $this->getClient()->zrangebyscore($this->buildKey($queue), 0, $this->currentTime(), ['LIMIT' => [0, 1]]);

        if (empty($id)) {
            return null;
        }

        if (is_array($id)) {
            $id = array_shift($id);
        }

        $job = $this->getJobById($queue, $id);

        if ($job->reserved() && $job->reservedAt() > ($this->currentTime() - $this->expire)) {
            return null;
        }

        return $job;
    }

    /**
     * Check if job exists in the queue.
     *
     * @param string $job
     * @param array $data
     * @param string|null $queue
     *
     * @return bool
     */
    public function exists(string $job, array $data = [], ?string $queue = null): bool
    {
        $cursor = new HashKey($this->getClient(), $this->buildKey($queue, 'payload'));
        $payload = $this->createPayload($job, $data);

        foreach ($cursor as $key => $value) {
            if ($value === $payload) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if can run process depend on limits
     *
     * @param JobContractInterface $job
     *
     * @return bool
     */
    public function canRunJob(JobContractInterface $job): bool
    {
        return $this->getClient()->zcount(
                $this->buildKey($job->getQueue(), 'reserved'),
                '-inf',
                '+inf'
            ) < $this->limit || $job->reserved();
    }

    /**
     * Get job by its id
     *
     * @param string $queue
     * @param string $id
     *
     * @return null|JobContractInterface
     */
    public function getJobById(string $queue, string $id): ?JobContractInterface
    {
        $job = $this->getClient()->hget($this->buildKey($queue, 'payload'), $id);

        if (!$job) {
            return null;
        } else {
            $reservedAt = $this->getClient()->zscore($this->buildKey($queue, 'reserved'), $id);
            $attempts = $this->getClient()->zscore($this->buildKey($queue, 'attempted'), $id);

            return new JobContract(
                $this->resolver,
                $this,
                $this->buildJob($id, $queue, $attempts ?? 0, json_decode($job, true), $reservedAt)
            );
        }
    }

    /**
     * Mark the given job ID as reserved.
     *
     * @param JobContractInterface $job
     *
     * @throws \Exception
     */
    public function markJobAsReserved(JobContractInterface $job)
    {
        $this->getClient()->pipeline(['atomic' => true])
            ->zadd($this->buildKey($job->getQueue(), 'reserved'), [
                $job->getJobId() => $this->currentTime(),
            ])
            ->zincrby($this->buildKey($job->getQueue(), 'attempted'), 1, $job->getJobId())
            ->execute();
    }

    /**
     * Delete a reserved job from the queue.
     *
     * @param string $queue
     * @param string $id
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteReserved(string $queue, string $id): bool
    {
        $this->getClient()->pipeline(['atomic' => true])
            ->hdel($this->buildKey($queue, 'payload'), [$id])
            ->zrem($this->buildKey($queue, 'reserved'), $id)
            ->zrem($this->buildKey($queue, 'attempted'), $id)
            ->zrem($this->buildKey($queue), $id)
            ->execute();

        return true;
    }

    /**
     * Release a reserved job back onto the queue.
     *
     * @param JobContractInterface $job
     * @param DateInterval|int $delay
     *
     * @return mixed
     * @throws \Exception
     */
    public function release(JobContractInterface $job, $delay)
    {
        return $this->pushToDatabase($delay, $job->getQueue(), $job->getRawBody(), $job->attempts());
    }

    /**
     * Build collection:queue:postfix key
     *
     * @param string|null $queue
     * @param string|null $postfix
     *
     * @return string
     */
    private function buildKey(?string $queue = 'default', ?string $postfix = null)
    {
        return "$this->collection:$queue" . ($postfix ? ":$postfix" : "");
    }

    /**
     * Get the "available at" UNIX timestamp.
     *
     * @param DateInterval|int $delay
     *
     * @return int
     */
    private function getAvailableAt($delay = 0)
    {
        return $delay instanceof DateInterval
            ? (new DateTime())->add($delay)->getTimestamp()
            : $this->currentTime() + $delay;
    }

    /**
     * Push job to database
     *
     * @param DateInterval|int $delay
     * @param string|null $queue
     * @param string $payload
     * @param int $attempts
     *
     * @throws \Exception
     */
    private function pushToDatabase($delay, ?string $queue, string $payload, int $attempts = 0)
    {
        $id = $this->getRandomId();

        $pipeline = $this->getClient()->pipeline(['atomic' => true])
            ->hset(
                $this->buildKey($queue, 'payload'),
                $id,
                $payload
            )
            ->zadd($this->buildKey($queue), [
                $id => $this->getAvailableAt($delay),
            ]);

        if ($attempts > 0) {
            $pipeline->zadd($this->buildKey($queue, 'attempted'), [
                $id => $attempts,
            ]);
        }

        $pipeline->execute();
    }

    /**
     * Build job from database record
     *
     * @param string $id
     * @param string $queue
     * @param int $attempts
     * @param array $payload
     * @param int|null $reservedAt
     *
     * @return Job
     */
    private function buildJob(string $id, string $queue, int $attempts, array $payload, ?int $reservedAt = null): Job
    {
        $job = new Job();
        $job->setId($id);
        $job->setAttempts($attempts);
        $job->setQueue($queue);
        $job->setReserved((bool)$reservedAt);
        $job->setReservedAt($reservedAt);
        $job->setPayload($payload);

        return $job;
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
}