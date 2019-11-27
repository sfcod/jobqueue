<?php

namespace SfCod\QueueBundle\Event;

use Exception;
use SfCod\QueueBundle\Job\JobContractInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class JobFailedEvent
 * Event on jobs failed
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Events
 */
class JobFailedEvent extends Event
{
    /**
     * The connection name.
     *
     * @var string
     */
    protected $connectionName;

    /**
     * The job instance.
     *
     * @var JobContractInterface
     */
    protected $job;

    /**
     * The exception that caused the job to fail.
     *
     * @var \Exception
     */
    protected $exception;

    /**
     * Create a new event instance.
     *
     * @param string $connectionName
     * @param JobContractInterface $job
     * @param Exception $exception
     * @param array $config
     */
    public function __construct(string $connectionName, JobContractInterface $job, Exception $exception)
    {
        $this->job = $job;
        $this->exception = $exception;
        $this->connectionName = $connectionName;
    }

    /**
     * @return string
     */
    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    /**
     * @return JobContractInterface
     */
    public function getJob(): JobContractInterface
    {
        return $this->job;
    }

    /**
     * @return Exception
     */
    public function getException(): Exception
    {
        return $this->exception;
    }
}
