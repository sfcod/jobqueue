<?php

namespace SfCod\QueueBundle\Queue;

use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Queue\Queue;
use MongoDB\Collection;
use SfCod\QueueBundle\Base\Job;
use SfCod\QueueBundle\Job\MongoJob;
use SfCod\QueueBundle\Service\MongoDriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
     * The mongo connection instance.
     *
     * @var \Illuminate\Database\Connection
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
    protected $queue;

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
    public function __construct(MongoDriverInterface $mongo,
                                string $collection,
                                string $queue = 'default',
                                int $expire = 60,
                                int $limit = 15
    )
    {
        $this->collection = $collection;
        $this->expire = $expire;
        $this->queue = $queue;
        $this->mongo = $mongo;
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
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushToDatabase(0, $queue, $this->createPayload($job, $data));
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string $queue
     *
     * @return null|Job
     */
    public function pop($queue = null)
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
     * @param mixed $data
     * @param string $queue
     *
     * @return mixed
     */
    public function exists($job, $data = '', $queue = null)
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
     * @param string $queue
     * @param array $options
     *
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return $this->pushToDatabase(0, $queue, $payload);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param DateTime|int $delay
     * @param string $job
     * @param mixed $data
     * @param string $queue
     *
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
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
    public function bulk($jobs, $data = '', $queue = null)
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
     * @param int $delay
     *
     * @return mixed
     */
    public function release($queue, $job, $delay)
    {
        return $this->pushToDatabase($delay, $queue, $job->payload, $job->attempts);
    }

    /**
     * Get the next available job for the queue.
     *
     * @param $id
     *
     * @return null|Job
     */
    public function getJobById($id)
    {
        $job = $this->getCollection()->findOne(['_id' => new \MongoDB\BSON\ObjectID($id)]);

        if (is_null($job)) {
            return null;
        } else {
            $job = (object)$job;

            return new MongoJob($this->container, $this, $job, $job->queue);
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
    public function deleteReserved($queue, $id): int
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
     * @param int|null $seconds
     */
    public function setExpire($seconds)
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
    public function size($queue = null)
    {
        if ($queue) {
            return $this->getCollection()->count(['queue' => $queue]);
        }

        return $this->getCollection()->count();
    }

    /**
     * Check if can run process depend on limits
     *
     * @param Job $job
     *
     * @return bool
     */
    public function canRunJob(Job $job)
    {
        if ($job->getQueue()) {
            return $this->getCollection()->count([
                    'reserved' => 1,
                    'queue' => $job->getQueue()
                ]) < $this->limit || $job->reserved();
        }

        return $this->getCollection()->count(['reserved' => 1]) < $this->limit || $job->reserved();
    }

    /**
     * Mark the given job ID as reserved.
     *
     * @param Job $job
     */
    public function markJobAsReserved($job)
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
     * @param DateTime|int $delay
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
     * @param DateTime|int $delay
     *
     * @return int
     */
    protected function getAvailableAt($delay)
    {
        $availableAt = $delay instanceof DateTime ? $delay : Carbon::now()->addSeconds($delay);

        return $availableAt->getTimestamp();
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
     * @return null|Job
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

        return $job ? new MongoJob($this->container, $this, (object)$job, ((object)$job)->queue) : null;
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
            'reserved_at' => ['$lte' => Carbon::now()->subSeconds($this->expire)->getTimestamp()],
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

    /**
     * @param ContainerInterface $container
     */
    public function putContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param Container $container
     *
     * @throws Exception
     */
    public function setContainer(Container $container)
    {
        // Nothing
    }
}
