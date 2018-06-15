<?php

namespace SfCod\QueueBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Connector\ConnectorInterface;
use SfCod\QueueBundle\Queue\QueueInterface;
use SfCod\QueueBundle\Service\QueueManager;

/**
 * Class QueueManagerTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\Service
 */
class QueueManagerTest extends TestCase
{
    /**
     * Test manager connection
     */
    public function testConnection()
    {
        $driver = uniqid('driver_');
        $connectionName = uniqid('connection_');

        $queueManager = $this->mockQueueManager($driver, $connectionName);

        $this->assertFalse($queueManager->connected($connectionName));

        $queue = $queueManager->connection($connectionName);

        $this->assertInstanceOf(QueueInterface::class, $queue);
        $this->assertTrue($queueManager->connected($connectionName));
    }

    /**
     * Mock queue manager
     *
     * @param string $driver
     * @param string $connectionName
     *
     * @return QueueManager
     */
    private function mockQueueManager(string $driver, string $connectionName): QueueManager
    {
        $config = [
            'driver' => $driver,
            'collection' => uniqid('collection_'),
            'queue' => uniqid('queue_'),
            'expire' => rand(60, 3600),
            'limit' => rand(1, 10),
        ];

        $queue = $this->createMock(QueueInterface::class);
        $queue
            ->expects($this->once())
            ->method('setConnectionName')
            ->with($this->equalTo($connectionName))
            ->will($this->returnSelf());

        $connector = $this->createMock(ConnectorInterface::class);
        $connector
            ->expects($this->once())
            ->method('connect')
            ->with($this->equalTo($config))
            ->will($this->returnValue($queue));

        $queueManager = new QueueManager();

        $queueManager->addConnector($driver, $connector);
        $queueManager->addConnection($config, $connectionName);

        return $queueManager;
    }
}
