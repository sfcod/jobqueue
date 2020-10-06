<?php

namespace SfCod\QueueBundle\Failer;

use Exception;
use MongoDB\BSON\ObjectID;
use MongoDB\Collection;
use MongoDB\DeleteResult;
use SfCod\QueueBundle\Base\MongoDriverInterface;
use SfCod\QueueBundle\Entity\Job;

/**
 * Mongo provider for failed jobs
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class MongoFailedJobProvider implements FailedJobProviderInterface
{
    /**
     * The database connection name.
     *
     * @var MongoDriverInterface
     */
    protected $mongo;

    /**
     * The database collection.
     *
     * @var string
     */
    protected $collection;

    /**
     * Create a new database failed job provider.
     *
     * @param MongoDriverInterface $mongo
     * @param string $collection
     */
    public function __construct(MongoDriverInterface $mongo, string $collection = 'queue_jobs_failed')
    {
        $this->mongo = $mongo;
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
     * @return int|null|void
     */
    public function log(string $connection, string $queue, string $payload, Exception $exception): void
    {
        $this->getCollection()->insertOne([
            'connection' => $connection,
            'queue' => $queue,
            'payload' => $payload,
            'exception' => $exception->getMessage(),
            'failed_at' => time(),
        ]);
    }

    /**
     * Get a list of all of the failed jobs.
     *
     * @return array
     */
    public function all(): array
    {
        $result = [];
        $jobs = $this->getCollection()->find([], [
            'sort' => ['_id' => -1],
        ]);

        foreach ($jobs as $job) {
            $result[] = $this->buildJob($job);
        }

        return $result;
    }

    /**
     * Get a single failed job.
     *
     * @param string $id
     *
     * @return Job
     */
    public function find(string $id): Job
    {
        $data = $this->getCollection()->findOne(['_id' => new ObjectID($id)]);

        return $this->buildJob($data);
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
        $result = $this->getCollection()->deleteOne(['_id' => new ObjectID($id)]);

        if ($result instanceof DeleteResult) {
            return (bool)$result->getDeletedCount();
        }

        return true;
    }

    /**
     * Flush all of the failed jobs from storage.
     */
    public function flush(): void
    {
        $this->getCollection()->drop();
    }

    /**
     * Get a new query builder instance for the collection.
     *
     * @return Collection mongo collection
     */
    protected function getCollection(): Collection
    {
        return $this->mongo->getDatabase()->selectCollection($this->collection);
    }

    /**
     * Build job from database data
     *
     * @param $data
     *
     * @return Job
     */
    protected function buildJob($data): Job
    {
        $job = new Job();
        $job->setId($data->_id);
        $job->setQueue($data->queue);
        $job->setAttempts($data->attempts ?? 0);
        $job->setReserved($data->reserved ?? false);
        $job->setReservedAt($data->reserved_at ?? null);
        $job->setPayload(json_decode($data->payload, true));

        return $job;
    }
}
