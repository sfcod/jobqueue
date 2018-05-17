<?php

namespace SfCod\QueueBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class JobProcessedEvent
 * Event on job processed event
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Events
 */
class JobProcessedEvent extends Event
{
    /**
     * The connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The job instance.
     *
     * @var \Illuminate\Contracts\Queue\Job
     */
    public $job;

    /**
     * Create a new event instance.
     *
     * @param string $connectionName
     * @param \Illuminate\Contracts\Queue\Job $job
     * @param array $config
     */
    public function __construct($connectionName, $job, array $config = [])
    {
        $this->job = $job;
        $this->connectionName = $connectionName;
    }
}
