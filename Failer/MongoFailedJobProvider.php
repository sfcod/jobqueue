<?php

namespace SfCod\QueueBundle\Failer;

use Carbon\Carbon;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use MongoDB\Collection;
use MongoDB\Database;
use SfCod\QueueBundle\Service\MongoDriver;

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
     * @var MongoDriver
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
     * @param MongoDriver $mongo
     * @param string $collection
     */
    public function __construct(MongoDriver $mongo, string $collection)
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
     * @param \Exception $exception
     *
     * @return int|null|void
     */
    public function log($connection, $queue, $payload, $exception)
    {
        $this->getCollection()->insertOne([
            'connection' => $connection,
            'queue' => $queue,
            'payload' => $payload,
            'exception' => $exception->getMessage(),
            'failed_at' => Carbon::now(),
        ]);
    }

    /**
     * Get a list of all of the failed jobs.
     *
     * @return array
     */
    public function all()
    {
        $result = [];
        $data = $this->getCollection()->find([], [
            'sort' => ['_id' => -1],
        ]);

        foreach ($data as $item) {
            $result[] = $item;
        }

        return $result;
    }

    /**
     * Get a single failed job.
     *
     * @param mixed $id
     *
     * @return array
     */
    public function find($id)
    {
        return $this->getCollection()->findOne(['_id' => new \MongoDB\BSON\ObjectID($id)]);
    }

    /**
     * Delete a single failed job from storage.
     *
     * @param mixed $id
     *
     * @return bool
     */
    public function forget($id)
    {
        return (bool)$this->getCollection()->deleteOne(['_id' => new \MongoDB\BSON\ObjectID($id)])->getDeletedCount();
    }

    /**
     * Flush all of the failed jobs from storage.
     */
    public function flush()
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
}
