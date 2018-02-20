<?php

namespace SfCod\QueueBundle\Service;

use Illuminate\Queue\Capsule\Manager;
use Illuminate\Queue\QueueManager;
use SfCod\QueueBundle\Base\JobQueueInterface;
use SfCod\QueueBundle\Connector\MongoConnector;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service for illuminate queues to work with mongodb
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 * @author Orlov Alexey <aaorlov88@gmail.com>
 */
class JobQueue implements JobQueueInterface
{
    use ContainerAwareTrait;

    /**
     * Available connections
     *
     * @var array
     */
    protected $connections = [
        'default' => [
            'driver' => 'mongo-thread',
            'collection' => 'queue_jobs',
            'queue' => 'default',
            'expire' => 60,
            'limit' => 2,
            'connectionName' => 'default',
        ],
    ];

    /**
     * @var MongoDriver
     */
    protected $mongo;

    /**
     * Manager instance
     *
     * @var Manager
     */
    protected $manager;

    /**
     * JobQueue constructor.
     *
     * @param ContainerInterface $container
     * @param MongoDriver $mongo
     * @param array $connections
     *
     * @internal param array $config
     */
    public function __construct(ContainerInterface $container, MongoDriver $mongo, array $connections = [])
    {
        $this->connections = array_merge($this->connections, $connections);
        $this->mongo = $mongo;
        $this->container = $container;

        $this->connect();
    }

    /**
     * Get queue manager instance
     *
     * @return QueueManager
     */
    public function getQueueManager(): QueueManager
    {
        return $this->manager->getQueueManager();
    }

    /**
     * Push new job to queue
     *
     * @author Virchenko Maksim <muslim1992@gmail.com>
     *
     * @param string $job
     * @param array $data
     * @param string $queue
     * @param string $connection
     *
     * @return mixed
     */
    public function push(string $job, array $data = [], string $queue = 'default', string $connection = 'default')
    {
        return Manager::push($job, $data, $queue, $connection);
    }

    /**
     * Push new job to queue if this job is not exist
     *
     * @author Virchenko Maksim <muslim1992@gmail.com>
     *
     * @param string $job
     * @param array $data
     * @param string $queue
     * @param string $connection
     *
     * @return mixed
     */
    public function pushUnique(string $job, array $data = [], string $queue = 'default', string $connection = 'default')
    {
        if (false === Manager::connection($connection)->exists($job, $data, $queue)) {
            return Manager::push($job, $data, $queue, $connection);
        }

        return null;
    }

    /**
     * Push a new an array of jobs onto the queue.
     *
     * @param array $jobs
     * @param mixed $data
     * @param string $queue
     * @param string $connection
     *
     * @return mixed
     */
    public function bulk(array $jobs, array $data = [], string $queue = 'default', string $connection = 'default')
    {
        return Manager::bulk($jobs, $data, $queue, $connection);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param \DateTime|int $delay
     * @param string $job
     * @param mixed $data
     * @param string $queue
     * @param string $connection
     *
     * @return mixed
     */
    public function later(int $delay, string $job, array $data = [], string $queue = 'default', string $connection = 'default')
    {
        return Manager::later($delay, $job, $data, $queue, $connection);
    }

    /**
     * Push a new job into the queue after a delay if job does not exist.
     *
     * @param \DateTime|int $delay
     * @param string $job
     * @param mixed $data
     * @param string $queue
     * @param string $connection
     *
     * @return mixed
     */
    public function laterUnique(int $delay, string $job, array $data = [], string $queue = 'default', string $connection = 'default')
    {
        if (false === Manager::connection($connection)->exists($job, $data, $queue)) {
            return Manager::later($delay, $job, $data, $queue, $connection);
        }

        return null;
    }

    /**
     * Connect queue manager for mongo database
     *
     * @author Virchenko Maksim <muslim1992@gmail.com>
     *
     * @return Manager
     */
    protected function connect()
    {
        if (is_null($this->manager)) {
            $this->manager = new Manager();

            $this->manager->addConnector('mongo-thread', function () {
                return new MongoConnector($this->mongo, $this->container);
            });

            foreach ($this->connections as $name => $params) {
                $this->manager->addConnection($params, $name);
            }

            $this->manager->setAsGlobal();
        }

        return $this->manager;
    }
}
