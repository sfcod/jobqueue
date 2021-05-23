<?php

namespace SfCod\QueueBundle\Connector;

use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Queue\QueueInterface;
use SfCod\QueueBundle\Queue\RedisQueue;
use SfCod\QueueBundle\Service\RedisDriver;

/**
 * Connector for queue to redis
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class RedisConnector implements ConnectorInterface
{
    /**
     * @var JobResolverInterface
     */
    protected $jobResolver;

    /**
     * @var RedisDriver
     */
    protected $redis;

    /**
     * RedisConnector constructor.
     *
     * @param JobResolverInterface $jobResolver
     * @param RedisDriver $redis
     */
    public function __construct(JobResolverInterface $jobResolver, RedisDriver $redis)
    {
        $this->jobResolver = $jobResolver;
        $this->redis = $redis;
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

        return new RedisQueue(
            $this->jobResolver,
            $this->redis,
            $config['collection'],
            $config['queue'],
            $config['expire'],
            $config['limit']
        );
    }
}
