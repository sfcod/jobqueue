<?php

namespace SfCod\QueueBundleTests\Connector;

use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Base\MongoDriverInterface;
use SfCod\QueueBundle\Connector\MongoConnector;
use SfCod\QueueBundle\Queue\MongoQueue;

/**
 * Class MongoConnectorTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\Connector
 */
class MongoConnectorTest extends TestCase
{
    /**
     * Test connect
     */
    public function testConnect(): void
    {
        $jobResolver = $this->createMock(JobResolverInterface::class);
        $mongoDriver = $this->createMock(MongoDriverInterface::class);

        $connector = new MongoConnector($jobResolver, $mongoDriver);

        $config = [
            'collection' => uniqid('collection_', true),
            'queue' => uniqid('queue_', true),
            'expire' => random_int(1, 1000),
            'limit' => random_int(1, 10),
        ];

        $queue = $connector->connect($config);

        self::assertInstanceOf(MongoQueue::class, $queue);
    }
}
