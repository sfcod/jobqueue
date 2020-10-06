<?php

namespace SfCod\QueueBundleTests\Failer;

use Exception;
use Helmich\MongoMock\MockDatabase;
use MongoDB\Database;
use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Base\MongoDriverInterface;
use SfCod\QueueBundle\Entity\Job;
use SfCod\QueueBundle\Failer\MongoFailedJobProvider;

/**
 * Class MongoFailedJobProviderTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\Failer
 */
class MongoFailedJobProviderTest extends TestCase
{
    /**
     * Test failed jobs logging
     */
    public function testLog(): void
    {
        [$connection, $queue, $payload, $exception, $collection] = $this->mockData();

        $database = new MockDatabase();
        $provider = $this->mockProvider($database, $collection);

        $provider->log($connection, $queue, $payload, $exception);

        $record = $database->selectCollection($collection)->findOne([
            'connection' => $connection,
            'queue' => $queue,
            'payload' => $payload,
            'exception' => $exception->getMessage(),
        ]);

        self::assertNotNull($record, 'Log missed in mongodb.');
    }

    /**
     * Test fetching all failed jobs
     */
    public function testAll(): void
    {
        [$connection, $queue, $payload, $exception, $collection] = $this->mockData();

        $database = new MockDatabase();
        $provider = $this->mockProvider($database, $collection);

        for ($i = 0; $i < 10; ++$i) {
            $provider->log($connection, $queue, $payload, $exception);
        }

        $count = $database->selectCollection($collection)->countDocuments();

        self::assertEquals(10, $count);
    }

    /**
     * Test find jobs
     */
    public function testFind(): void
    {
        [$connection, $queue, $payload, $exception, $collection] = $this->mockData();

        $database = new MockDatabase();
        $provider = $this->mockProvider($database, $collection);

        $provider->log($connection, $queue, $payload, $exception);

        $record = $database->selectCollection($collection)->findOne([
            'connection' => $connection,
            'queue' => $queue,
            'payload' => $payload,
            'exception' => $exception->getMessage(),
        ]);

        self::assertNotNull($record);
        self::assertInstanceOf(Job::class, $provider->find($record->_id));
    }

    /**
     * Test forget failed job
     */
    public function testForget(): void
    {
        [$connection, $queue, $payload, $exception, $collection] = $this->mockData();

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

        self::assertNull($record);
    }

    /**
     * Test flush failed jobs
     */
    public function testFlush(): void
    {
        [$connection, $queue, $payload, $exception, $collection] = $this->mockData();

        $database = new MockDatabase();
        $provider = $this->mockProvider($database, $collection);

        for ($i = 0; $i < 10; ++$i) {
            $provider->log($connection, $queue, $payload, $exception);
        }

        $count = $database->selectCollection($collection)->countDocuments();

        self::assertEquals(10, $count);

        $provider->flush();
        $count = $database->selectCollection($collection)->countDocuments();

        self::assertEquals(0, $count);
    }

    /**
     * Mock data
     *
     * @return array
     */
    private function mockData(): array
    {
        return array_values([
            'connection' => uniqid('connection_', true),
            'queue' => uniqid('queue_', true),
            'payload' => json_encode(range(1, 10)),
            'exception' => new Exception(uniqid('message_', true)),
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
            ->method('getDatabase')
            ->willReturn($database);

        return new MongoFailedJobProvider($mongo, $collection);
    }
}
