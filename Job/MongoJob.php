<?php

namespace SfCod\QueueBundle\Job;

use Illuminate\Contracts\Queue\Job as JobContract;
use SfCod\QueueBundle\Base\Job;
use SfCod\QueueBundle\Queue\MongoQueue;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * MongoJob for laravel queue
 *
 * @author Alexey Orlov <aaorlov88@gmail.com>
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class MongoJob extends Job implements JobContract
{
    /**
     * The database queue instance.
     *
     * @var MongoQueue
     */
    protected $database;

    /**
     * The database job payload.
     *
     * @var StdClass
     */
    protected $job;

    /**
     * Create a new job instance.
     *
     * @param ContainerInterface $container
     * @param MongoQueue $database
     * @param StdClass $job
     * @param string $queue
     */
    public function __construct(ContainerInterface $container, MongoQueue $database, $job, $queue)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->database = $database;
        $this->container = $container;
    }

    /**
     * Delete the job from the queue.
     */
    public function delete()
    {
        parent::delete();

        if ($this->database->deleteReserved($this->queue, (string)$this->getJobId())) {
            $this->deleted = true;
        }
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int
    {
        return (int)$this->job->attempts;
    }

    /**
     * Check if job reserved
     *
     * @return bool
     */
    public function reserved(): bool
    {
        return (bool)$this->job->reserved;
    }

    /**
     * Get reserved at time
     *
     * @return int
     */
    public function reservedAt(): int
    {
        return (int)$this->job->reserved_at;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId(): string
    {
        return (string)$this->job->_id;
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->job->payload;
    }
}
