<?php

namespace SfCod\QueueBundle\Tests\Failer;

use Helmich\MongoMock\MockDatabase;
use MongoDB\Database;
use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Base\MongoDriverInterface;
use SfCod\QueueBundle\Failer\MongoFailedJobProvider;
use Exception;

/**
 * Class MongoFailedJobProviderTest
 * @author Virchenko Maksim <muslim1992@gmail.com>
 * @package SfCod\QueueBundle\Tests\Failer
 */
class MongoFailedJobProviderTest extends TestCase
{
    /**
     * Test failed jobs logging
     */
    public function testLog()
    {
        list($connection, $queue, $payload, $exception, $collection) = $this->mockData();

        $database = new MockDatabase();
        $provider = $this->mockProvider($database, $collection);

        $provider->log($connection, $queue, $payload, $exception);

        $record = $database->selectCollection($collection)->findOne([
            'connection' => $connection,
            'queue' => $queue,
            'payload' => $payload,
            'exception' => $exception->getMessage(),
        ]);

        $this->assertNotNull($record, 'Log missed in mongodb.');
    }

    /**
     * Test fetching all failed jobs
     */
    public function testAll()
    {
        list($connection, $queue, $payload, $exception, $collection) = $this->mockData();

        $database = new MockDatabase();
        $provider = $this->mockProvider($database, $collection);

        for ($i = 0; $i < 10; $i++) {
            $provider->log($connection, $queue, $payload, $exception);
        }

        $count = $database->selectCollection($collection)->count();

        $this->assertEquals(10, $count);
    }

    /**
     * Test find jobs
     */
    public function testFind()
    {
        list($connection, $queue, $payload, $exception, $collection) = $this->mockData();

        $database = new MockDatabase();
        $provider = $this->mockProvider($database, $collection);

        $provider->log($connection, $queue, $payload, $exception);

        $record = $database->selectCollection($collection)->findOne([
            'connection' => $connection,
            'queue' => $queue,
            'payload' => $payload,
            'exception' => $exception->getMessage(),
        ]);

        $this->assertEquals($record, $provider->find($record->_id));
    }

    /**
     * Test forget failed job
     */
    public function testForget()
    {
        list($connection, $queue, $payload, $exception, $collection) = $this->mockData();

        $database = new MockDatabase();
        $provider = $this->mockProvider($database, $collection);

        $provider->log($connection, $queue, $payload, $exception);

        $record = $database->selectCollection($collection)->findOne([
            'connection' => $connection,
            'queue' => $queue,
            'payload' => $payload,
            'exception' => $exception->getMessage(),
        ]);

        $provider->forget($record->_id);

        $record = $database->selectCollection($collection)->findOne(['_id' => $record->_id]);

        $this->assertNull($record);
    }

    /**
     * Test flush failed jobs
     */
    public function testFlush()
    {
        list($connection, $queue, $payload, $exception, $collection) = $this->mockData();

        $database = new MockDatabase();
        $provider = $this->mockProvider($database, $collection);

        for ($i = 0; $i < 10; $i++) {
            $provider->log($connection, $queue, $payload, $exception);
        }

        $count = $database->selectCollection($collection)->count();

        $this->assertEquals(10, $count);

        $provider->flush();
        $count = $database->selectCollection($collection)->count();

        $this->assertEquals(0, $count);
    }

    /**
     * Mock data
     *
     * @return array
     */
    private function mockData(): array
    {
        return array_values([
            'connection' => uniqid('connection_'),
            'queue' => uniqid('queue_'),
            'payload' => json_encode(range(1, 10)),
            'exception' => new Exception(uniqid('message_')),
            'collection' => 'queue_jobs_failed_test',
        ]);
    }

    /**
     * Mock mongo failed provider
     *
     * @param Database $database
     * @param string $collection
     *
     * @return MongoFailedJobProvider
     */
    private function mockProvider(Database $database, string $collection): MongoFailedJobProvider
    {
        $mongo = $this->createMock(MongoDriverInterface::class);
        $mongo
            ->expects($this->any())
            ->method('getDatabase')
            ->will($this->returnValue($database));

        $provider = new MongoFailedJobProvider($mongo, $collection);

        return $provider;
    }
}