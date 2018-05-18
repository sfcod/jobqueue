<?php

namespace SfCod\QueueBundle\Job;

use SfCod\QueueBundle\Base\JobInterface;
use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Queue\QueueInterface;
use stdClass;

/**
 * MongoJob for laravel queue
 *
 * @author Alexey Orlov <aaorlov88@gmail.com>
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class MongoJobContract extends JobContract implements JobContractInterface
{
    /**
     * Job resolver
     *
     * @var JobResolverInterface
     */
    protected $resolver;

    /**
     * The database queue instance.
     *
     * @var QueueInterface
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
     * @param JobResolverInterface $resolver
     * @param QueueInterface $database
     * @param StdClass|MongoDB\Model\BSONDocument $job
     * @param string $queue
     */
    public function __construct(JobResolverInterface $resolver, QueueInterface $database, $job, string $queue)
    {
        $this->resolver = $resolver;
        $this->database = $database;
        $this->job = $job;
        $this->queue = $queue;
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
    public function getRawBody(): string
    {
        return $this->job->payload;
    }

    /**
     * Resolve job
     *
     * @param string $class
     *
     * @return JobInterface
     */
    protected function resolve(string $class): JobInterface
    {
        return $this->resolver->resolve($class);
    }
}
