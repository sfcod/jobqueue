<?php

namespace SfCod\QueueBundle\Connector;

use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Queue\MongoQueue;
use SfCod\QueueBundle\Queue\QueueInterface;
use SfCod\QueueBundle\Service\MongoDriver;

/**
 * Connector for queue to mongodb
 *
 * @author Orlov Aleksey <aaorlov88@gmail.com>
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class MongoConnector implements ConnectorInterface
{
    /**
     * @var JobResolverInterface
     */
    protected $jobResolver;

    /**
     * @var MongoDriver
     */
    protected $mongo;

    /**
     * MongoConnector constructor.
     *
     * @param JobResolverInterface $jobResolver
     * @param MongoDriver $mongo
     */
    public function __construct(JobResolverInterface $jobResolver, MongoDriver $mongo)
    {
        $this->jobResolver = $jobResolver;
        $this->mongo = $mongo;
    }

    /**
     * Establish a queue database.
     *
     * @param array $config
     *
     * @return QueueInterface
     */
    public function connect(array $config): QueueInterface
    {
        $config = array_merge([
            'limit' => 15,
        ], $config);

        $queue = new MongoQueue(
            $this->jobResolver,
            $this->mongo,
            $config['collection'],
            $config['queue'],
            $config['expire'],
            $config['limit']
        );

        return $queue;
    }
}
