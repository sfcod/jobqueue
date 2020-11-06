<?php

namespace SfCod\QueueBundle\Tests\Connector;

use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Connector\RedisConnector;
use SfCod\QueueBundle\Queue\RedisQueue;
use SfCod\QueueBundle\Service\RedisDriver;

/**
 * Class RedisConnectorTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\Connector
 */
class RedisConnectorTest extends TestCase
{
    /**
     * Test connect
     */
    public function testConnect()
    {
        $jobResolver = $this->createMock(JobResolverInterface::class);
        $mongoDriver = $this->createMock(RedisDriver::class);

        $connector = new RedisConnector($jobResolver, $mongoDriver);

        $config = [
            'collection' => uniqid('collection_'),
            'queue' => uniqid('queue_'),
            'expire' => rand(1, 1000),
            'limit' => rand(1, 10),
        ];

        $queue = $connector->connect($config);

        self::assertInstanceOf(RedisQueue::class, $queue);
    }
}
