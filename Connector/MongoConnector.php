<?php

namespace SfCod\QueueBundle\Connector;

use Illuminate\Queue\Connectors\ConnectorInterface;
use SfCod\QueueBundle\Queue\MongoQueue;
use SfCod\QueueBundle\Service\MongoDriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Connector for laravel queue to mongodb
 *
 * @author Orlov Aleksey <aaorlov88@gmail.com>
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class MongoConnector implements ConnectorInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Create a new connector instance.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Establish a queue database.
     *
     * @param array $config
     *
     * @return MongoQueue
     */
    public function connect(array $config)
    {
        $config = array_merge([
            'limit' => 15,
            'connection' => MongoDriverInterface::class,
        ], $config);

        $mongoQueue = new MongoQueue($this->container->get($config['connection']), $config['collection'], $config['queue'], $config['expire'], $config['limit']);
        $mongoQueue->putContainer($this->container);

        return $mongoQueue;
    }
}
