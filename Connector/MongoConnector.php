<?php

namespace SfCod\QueueBundle\Connector;

use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Base\MongoDriverInterface;
use SfCod\QueueBundle\Queue\MongoQueue;
use SfCod\QueueBundle\Queue\QueueInterface;

/**
 * Connector for laravel queue to mongodb
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
     * @var MongoDriverInterface
     */
    protected $mongoDriver;

    /**
     * MongoConnector constructor.
     *
     * @param JobResolverInterface $jobResolver
     * @param MongoDriverInterface $mongoDriver
     */
    public function __construct(JobResolverInterface $jobResolver, MongoDriverInterface $mongoDriver)
    {
        $this->jobResolver = $jobResolver;
        $this->mongoDriver = $mongoDriver;
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

        return new MongoQueue(
            $this->jobResolver,
            $this->mongoDriver,
            $config['collection'],
            $config['queue'],
            $config['expire'],
            $config['limit']
        );
    }
}
