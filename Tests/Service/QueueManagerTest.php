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

        self::assertFalse($queueManager->connected($connectionName));

        $queue = $queueManager->connection($connectionName);

        self::assertInstanceOf(QueueInterface::class, $queue);
        self::assertTrue($queueManager->connected($connectionName));
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
            ->expects(self::once())
            ->method('setConnectionName')
            ->with(self::equalTo($connectionName))
            ->will(self::returnSelf());

        $connector = $this->createMock(ConnectorInterface::class);
        $connector
            ->expects(self::once())
            ->method('connect')
            ->with(self::equalTo($config))
            ->willReturn($queue);

        $queueManager = new QueueManager();

        $queueManager->addConnector($driver, $connector);
        $queueManager->addConnection($config, $connectionName);

        return $queueManager;
    }
}
