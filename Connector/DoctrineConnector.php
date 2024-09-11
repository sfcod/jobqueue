<?php

namespace SfCod\QueueBundle\Connector;

use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Queue\DoctrineQueue;
use SfCod\QueueBundle\Queue\QueueInterface;
use Doctrine\DBAL\Connection;

/**
 * Connector for queue to Doctrine
 */
class DoctrineConnector implements ConnectorInterface
{
    /**
     * @var JobResolverInterface
     */
    protected $jobResolver;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * DoctrineConnector constructor.
     *
     * @param JobResolverInterface $jobResolver
     * @param Connection $connection
     */
    public function __construct(JobResolverInterface $jobResolver, Connection $connection)
    {
        $this->jobResolver = $jobResolver;
        $this->connection = $connection;
    }

    /**
     * Establish a queue connection.
     *
     * @param array $config
     *
     * @return QueueInterface
     */
    public function connect(array $config): QueueInterface
    {
        // Merge default configuration with provided configuration
        $config = array_merge([
            'collection' => 'queue_jobs',
            'queue' => 'default',
            'expire' => 60,
        ], $config);

        // Create and return the DoctrineQueue instance
        return new DoctrineQueue(
            $this->jobResolver,
            $this->connection,
            $config['collection'],
            $config['queue'],
            $config['expire'],
            $config['limit']
        );
    }
}
