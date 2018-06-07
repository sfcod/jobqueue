<?php

namespace SfCod\QueueBundle\Queue;

use DateInterval;
use DateTime;
use MongoDB\Collection;
use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Base\MongoDriverInterface;
use SfCod\QueueBundle\Job\JobContractInterface;
use SfCod\QueueBundle\Job\MongoJobContract;

/**
 * Class MongoQueue
 *
 * @author Alexey Orlov <aaorlov88@gmail.com>
 *
 * @package yiiSfCod\jobqueue\queues
 */
class MongoQueue extends Queue
{
    /**
     * Job resolver
     *
     * @var JobResolverInterface
     */
    protected $resolver;

    /**
     * The mongo connection instance.
     *
     * @var MongoDriverInterface
     */
    protected $mongo;

    /**
     * The mongo collection that holds the jobs.
     *
     * @var string
     */
    protected $collection;

    /**
     * The name of the default queue.
     *
     * @var string
     */
    protected $queue = 'default';

    /**
     * The expiration time of a job.
     *
     * @var int|null
     */
    protected $expire = 60;

    /**
     * @var int
     */
    protected $limit = 15;

    /**
     * Create a new mongo queue instance.
     *
     * @param MongoDriverInterface $mongo
     * @param string $collection
     * @param string $queue
     * @param int $expire
     * @param int $limit
     */
    public function __construct(
        JobResolverInterface $resolver,
        MongoDriverInterface $mongo,
        string $collection,
        string $queue = 'default',
        int $expire = 60,
        int $limit = 15
    ) {
        $this->resolver = $resolver;
        $this->mongo = $mongo;
        $this->collection = $collection;
        $this->expire = $expire;
        $this->queue = $queue;
        $this->limit = $limit;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param string $job
     * @param mixed $data
     * @param string $queue
     *
     * @return mixed
     */
    public function push(string $job, array $data = [], ?string $queue = null)
    {
        return $this->pushToDatabase(0, $queue, $this->createPayload($job, $data));
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string $queue
     *
     * @return null|JobContractInterface
     */
    public function pop(?string $queue = null): ?JobContractInterface
    {
        $queue = $this->getQueue($queue);

        if ($job = $this->getNextAvailableJob($queue)) {
            return $job;
        }

        return null;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param string $job
     * @param array $data
     * @param string|null $queue
     *
     * @return bool
     */
    public function exists(string $job, array $data = [], ?string $queue = null): bool
    {
        return null !== $this->getCollection()->findOne([
                'queue' => $queue,
                'payload' => $this->createPayload($job, $data),
            ]);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string|null $queue
     * @param array $options
     *
     * @return mixed
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
     * @param string $queue
     *
     * @return mixed
     */
    public function later($delay, string $job, array $data = [], ?string $queue = null)
    {
        return $this->pushToDatabase($delay, $queue, $this->createPayload($job, $data));
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param array $jobs
     * @param mixed $data
     * @param string $queue
     *
     * @return mixed
     */
    public function bulk(array $jobs, array $data = [], ?string $queue = null)
    {
        $queue = $this->getQueue($queue);

        $availableAt = $this->getAvailableAt(0);

        $records = array_map(function ($job) use ($queue, $data, $availableAt) {
            return $this->buildDatabaseRecord($queue, $this->createPayload($job, $data), $availableAt);
        }, (array)$jobs);

        return $this->getCollection()->insertOne($records);
    }

    /**
     * Release a reserved job back onto the queue.
     *
     * @param string $queue
     * @param \StdClass $job
     * @param DateInterval|int $delay
     *
     * @return mixed
     */
    public function release(string $queue, StdClass $job, $delay)
    {
        return $this->pushToDatabase($delay, $queue, $job->payload, $job->attempts);
    }

    /**
     * Get the next available job for the queue.
     *
     * @param $id
     *
     * @return null|JobContractInterface
     */
    public function getJobById($id): ?JobContractInterface
    {
        $job = $this->getCollection()->findOne(['_id' => new \MongoDB\BSON\ObjectID($id)]);

        if (is_null($job)) {
            return null;
        } else {
            $job = (object)$job;

            return new MongoJobContract($this->resolver, $this, $job, $job->queue);
        }
    }

    /**
     * Delete a reserved job from the queue.
     *
     * @param string $queue
     * @param string $id
     *
     * @return int
     */
    public function deleteReserved(string $queue, $id): int
    {
        $query = [
            '_id' => new \MongoDB\BSON\ObjectID($id),
            'queue' => $queue,
        ];

        return $this->getCollection()->deleteOne($query)->getDeletedCount();
    }

    /**
     * Get the expiration time in seconds.
     *
     * @return int|null
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * Set the expiration time in seconds.
     *
     * @param int $seconds
     */
    public function setExpire(int $seconds)
    {
        $this->expire = $seconds;
    }

    /**
     * Get the size of the queue.
     *
     * @param string $queue
     *
     * @return int
     */
    public function size(?string $queue = null): int
    {
        if ($queue) {
            return $this->getCollection()->count(['queue' => $queue]);
        }

        return $this->getCollection()->count();
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
        return $this->getCollection()->count([
                'reserved' => 1,
                'queue' => $job->getQueue(),
            ]) < $this->limit || $job->reserved();
    }

    /**
     * Mark the given job ID as reserved.
     *
     * @param JobContractInterface $job
     */
    public function markJobAsReserved(JobContractInterface $job)
    {
        $attempts = $job->attempts() + 1;
        $reserved_at = $this->currentTime();

        $this->getCollection()->updateOne(['_id' => new \MongoDB\BSON\ObjectID($job->getJobId())], [
            '$set' => [
                'attempts' => $attempts,
                'reserved' => 1,
                'reserved_at' => $reserved_at,
            ],
        ]);
    }

    /**
     * Push a raw payload to the mongo with a given delay.
     *
     * @param DateInterval|int $delay
     * @param string|null $queue
     * @param string $payload
     * @param int $attempts
     *
     * @return mixed
     */
    protected function pushToDatabase($delay, $queue, $payload, $attempts = 0)
    {
        $attributes = $this->buildDatabaseRecord($this->getQueue($queue), $payload, $this->getAvailableAt($delay), $attempts);

        return $this->getCollection()->insertOne($attributes);
    }

    /**
     * Get the "available at" UNIX timestamp.
     *
     * @param DateInterval|int $delay
     *
     * @return int
     */
    protected function getAvailableAt($delay = 0)
    {
        return $delay instanceof DateInterval
            ? (new DateTime())->add($delay)->getTimestamp()
            : $this->currentTime() + $delay;
    }

    /**
     * Get the queue or return the default.
     *
     * @param string|null $queue
     *
     * @return string
     */
    protected function getQueue($queue)
    {
        return $queue ?: $this->queue;
    }

    /**
     * Get the next available job for the queue.
     *
     * @param string|null $queue
     *
     * @return null|JobContractInterface
     */
    protected function getNextAvailableJob($queue)
    {
        $job = $this->getCollection()
            ->findOne([
                'queue' => $this->getQueue($queue),
                '$or' => [
                    $this->isAvailable(),
                    $this->isReservedButExpired(),
                ],
            ], [
                'sort' => ['_id' => 1],
            ]);

        return $job ? new MongoJobContract($this->resolver, $this, (object)$job, ((object)$job)->queue) : null;
    }

    /**
     * Create an array to insert for the given job.
     *
     * @param string|null $queue
     * @param string $payload
     * @param int $availableAt
     * @param int $attempts
     *
     * @return array
     */
    protected function buildDatabaseRecord($queue, $payload, $availableAt, $attempts = 0)
    {
        return [
            'queue' => $queue,
            'payload' => $payload,
            'attempts' => $attempts,
            'reserved' => 0,
            'reserved_at' => null,
            'available_at' => $availableAt,
            'created_at' => $this->currentTime(),
        ];
    }

    /**
     * Get available jobs
     *
     * @return array
     */
    protected function isAvailable()
    {
        return [
            'reserved_at' => null,
            'available_at' => ['$lte' => $this->currentTime()],
        ];
    }

    /**
     * Get reserved but expired by time jobs
     *
     * @return array
     */
    protected function isReservedButExpired()
    {
        return [
            'reserved_at' => ['$lte' => $this->currentTime() - $this->expire],
        ];
    }

    /**
     * Get queue collection
     *
     * @return Collection Mongo collection instance
     */
    protected function getCollection(): Collection
    {
        return $this->mongo->getDatabase()->selectCollection($this->collection);
    }
}
