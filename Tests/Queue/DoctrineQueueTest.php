<?php

namespace SfCod\QueueBundle\Tests\Queue;

use DateInterval;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Entity\Job;
use SfCod\QueueBundle\Job\JobContract;
use SfCod\QueueBundle\Queue\DoctrineQueue;

/**
 * Class DoctrineQueueTest
 *
 * Runs against an in-memory SQLite database via Doctrine DBAL.
 * The queue's clock is frozen (see mockDoctrineQueue) so that delays
 * can be asserted deterministically without sleep().
 *
 * @package SfCod\QueueBundle\Tests\Queue
 */
class DoctrineQueueTest extends TestCase
{
    private const TABLE = 'queue_jobs';

    /**
     * Frozen "now" used by the queue under test.
     *
     * @var int
     */
    private int $now = 1_700_000_000;

    /**
     * Test pushing into database
     */
    public function testPush()
    {
        $jobName = uniqid('job_');
        $data = range(1, 10);

        $connection = $this->createConnection();
        $queue = $this->mockDoctrineQueue($connection);

        $queue->push($jobName, $data);

        $row = $this->fetchSingleRow($connection);

        $payload = json_decode(trim($row['payload'], "'"), true);
        self::assertEquals($jobName, $payload['job']);
        self::assertEquals($data, $payload['data']);
        self::assertEquals(0, $row['reserved']);
        self::assertNull($row['reserved_at']);
        self::assertEquals($this->now, $row['available_at']);
        self::assertEquals($this->now, $row['created_at']);
    }

    /**
     * Test pop from queue
     */
    public function testPop()
    {
        $jobName = uniqid('job_');
        $data = range(1, 10);

        $connection = $this->createConnection();
        $queue = $this->mockDoctrineQueue($connection);

        $queue->push($jobName, $data);

        $job = $queue->pop();

        self::assertNotNull($job);
        self::assertEquals($jobName, $job->getName());
        self::assertEquals($data, $job->payload()['data']);
    }

    /**
     * Test that pop on an empty queue returns null
     */
    public function testPopEmpty()
    {
        $queue = $this->mockDoctrineQueue($this->createConnection());

        self::assertNull($queue->pop());
    }

    /**
     * Test that later() writes available_at into the future
     */
    public function testLater()
    {
        $jobName = uniqid('job_');
        $data = range(1, 10);
        $delay = rand(60, 3600);

        $connection = $this->createConnection();
        $queue = $this->mockDoctrineQueue($connection);

        $queue->later($delay, $jobName, $data);

        $row = $this->fetchSingleRow($connection);

        $payload = json_decode(trim($row['payload'], "'"), true);
        self::assertEquals($jobName, $payload['job']);
        self::assertEquals($data, $payload['data']);
        self::assertEquals($this->now + $delay, $row['available_at']);
    }

    /**
     * Test that later() accepts a DateInterval delay
     */
    public function testLaterWithDateInterval()
    {
        $connection = $this->createConnection();
        $queue = $this->mockDoctrineQueue($connection);

        $queue->later(new DateInterval('PT90S'), uniqid('job_'));

        $row = $this->fetchSingleRow($connection);

        self::assertEquals($this->now + 90, $row['available_at']);
    }

    /**
     * Regression: a delayed job must not be popped before available_at.
     *
     * Previously getNextAvailableJob() had no available_at predicate, so
     * later() / laterUnique() / release($job, $delay) were silent no-ops.
     */
    public function testPopSkipsDelayedJobUntilAvailable()
    {
        $jobName = uniqid('job_');
        $delay = 300;

        $connection = $this->createConnection();
        $queue = $this->mockDoctrineQueue($connection);

        $queue->later($delay, $jobName);

        self::assertEquals(1, $queue->size(), 'Job must be stored');
        self::assertNull($queue->pop(), 'Delayed job must not be popped immediately');

        $this->now += $delay - 1;
        self::assertNull($queue->pop(), 'Delayed job must not be popped one second early');

        $this->now += 1;
        $job = $queue->pop();
        self::assertNotNull($job, 'Job must be popped once available_at is reached');
        self::assertEquals($jobName, $job->getName());
    }

    /**
     * Regression: a delayed job with a lower id must not shadow a ready job.
     */
    public function testPopReturnsReadyJobBehindDelayedOne()
    {
        $delayedName = uniqid('delayed_');
        $readyName = uniqid('ready_');

        $connection = $this->createConnection();
        $queue = $this->mockDoctrineQueue($connection);

        $queue->later(3600, $delayedName);
        $queue->push($readyName);

        $job = $queue->pop();

        self::assertNotNull($job);
        self::assertEquals($readyName, $job->getName());
    }

    /**
     * Test that ready jobs are popped in insertion order
     */
    public function testPopOrdersById()
    {
        $connection = $this->createConnection();
        $queue = $this->mockDoctrineQueue($connection);

        $queue->push('first');
        $queue->push('second');

        self::assertEquals('first', $queue->pop()->getName());
    }

    /**
     * Test release
     */
    public function testRelease()
    {
        $jobName = uniqid('job_');
        $data = range(1, 10);

        $connection = $this->createConnection();
        $queue = $this->mockDoctrineQueue($connection);

        $queue->push($jobName, $data);

        $row = $this->fetchSingleRow($connection);
        $connection->executeStatement(sprintf('DELETE FROM %s', self::TABLE));

        $queue->release($this->buildJobContract($queue, $row), 0);

        self::assertEquals(1, $queue->size());

        $released = $this->fetchSingleRow($connection);
        self::assertEquals($row['attempts'], $released['attempts']);
        self::assertEquals(json_decode(trim($row['payload'], "'"), true), json_decode(trim($released['payload'], "'"), true));
    }

    /**
     * Regression: release() with a delay must actually delay the retry.
     */
    public function testReleaseWithDelay()
    {
        $jobName = uniqid('job_');
        $delay = 120;

        $connection = $this->createConnection();
        $queue = $this->mockDoctrineQueue($connection);

        $queue->push($jobName);

        $row = $this->fetchSingleRow($connection);
        $connection->executeStatement(sprintf('DELETE FROM %s', self::TABLE));

        $queue->release($this->buildJobContract($queue, $row), $delay);

        self::assertEquals($this->now + $delay, $this->fetchSingleRow($connection)['available_at']);
        self::assertNull($queue->pop(), 'Released job must not be popped before its delay elapses');

        $this->now += $delay;
        $job = $queue->pop();
        self::assertNotNull($job);
        self::assertEquals($jobName, $job->getName());
    }

    /**
     * Test that a reserved job is re-popped only after it expires
     */
    public function testPopReturnsExpiredReservedJob()
    {
        $expire = 60;

        $connection = $this->createConnection();
        $queue = $this->mockDoctrineQueue($connection, $expire);

        $queue->push(uniqid('job_'));

        $job = $queue->pop();
        $queue->markJobAsReserved($job);

        self::assertNull($queue->pop(), 'Reserved job must not be popped while reservation is fresh');

        $this->now += $expire;
        self::assertNotNull($queue->pop(), 'Reserved job must be popped again once reservation expired');
    }

    /**
     * Test if job exists
     */
    public function testExists()
    {
        $jobName = uniqid('job_');
        $data = range(1, 10);

        $queue = $this->mockDoctrineQueue($this->createConnection());

        self::assertFalse($queue->exists($jobName, $data));

        $queue->push($jobName, $data);

        self::assertTrue($queue->exists($jobName, $data));
        self::assertFalse($queue->exists($jobName, [1]));
    }

    /**
     * Test pushRaw
     */
    public function testPushRaw()
    {
        $payload = json_encode(['job' => uniqid('job_'), 'data' => range(1, 10)]);

        $connection = $this->createConnection();
        $queue = $this->mockDoctrineQueue($connection);

        $queue->pushRaw($payload);

        self::assertEquals($payload, $this->fetchSingleRow($connection)['payload']);
    }

    /**
     * Test pushing bulk
     */
    public function testBulk()
    {
        $jobName = uniqid('job_');

        $queue = $this->mockDoctrineQueue($this->createConnection());

        $jobs = [];
        for ($i = 0; $i < 10; ++$i) {
            $jobs[] = $jobName . $i;
        }

        $queue->bulk($jobs, range(1, 10));

        self::assertEquals(10, $queue->size());
    }

    /**
     * Test getting job by id
     */
    public function testGetJobById()
    {
        $jobName = uniqid('job_');

        $connection = $this->createConnection();
        $queue = $this->mockDoctrineQueue($connection);

        $queue->push($jobName);

        $row = $this->fetchSingleRow($connection);

        $job = $queue->getJobById('default', (string)$row['id']);

        self::assertNotNull($job);
        self::assertEquals($jobName, $job->getName());
        self::assertNull($queue->getJobById('default', (string)($row['id'] + 1)));
    }

    /**
     * Test deleting reserved job
     */
    public function testDeleteReserved()
    {
        $connection = $this->createConnection();
        $queue = $this->mockDoctrineQueue($connection);

        $queue->push(uniqid('job_'));

        $row = $this->fetchSingleRow($connection);

        self::assertTrue($queue->deleteReserved('default', (string)$row['id']));
        self::assertEquals(0, $queue->size());
    }

    /**
     * Test queue's size per queue name
     */
    public function testSize()
    {
        $queue = $this->mockDoctrineQueue($this->createConnection());

        $queue->push(uniqid('job_'));
        $queue->push(uniqid('job_'));
        $queue->push(uniqid('job_'), [], 'other');

        self::assertEquals(2, $queue->size());
        self::assertEquals(1, $queue->size('other'));
    }

    /**
     * Test marking job as reserved
     */
    public function testMarkJobAsReserved()
    {
        $connection = $this->createConnection();
        $queue = $this->mockDoctrineQueue($connection);

        $queue->push(uniqid('job_'));

        $job = $queue->pop();
        $queue->markJobAsReserved($job);

        $row = $this->fetchSingleRow($connection);

        self::assertEquals(1, $row['reserved']);
        self::assertEquals($this->now, $row['reserved_at']);
        self::assertEquals(1, $row['attempts']);
    }

    /**
     * Test canRunJob respects the concurrency limit
     */
    public function testCanRunJob()
    {
        $connection = $this->createConnection();
        $queue = $this->mockDoctrineQueue($connection, 60, 1);

        $queue->push(uniqid('job_'));
        $queue->push(uniqid('job_'));

        $first = $queue->pop();
        self::assertTrue($queue->canRunJob($first));

        $queue->markJobAsReserved($first);

        $reserved = $queue->getJobById('default', (string)$first->getJobId());
        self::assertTrue($queue->canRunJob($reserved), 'Already reserved job may continue');

        $second = $queue->pop();
        self::assertFalse($queue->canRunJob($second), 'Limit of 1 reached by the reserved job');
    }

    private function createConnection(): Connection
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

        $connection->executeStatement(sprintf(
            'CREATE TABLE %s (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                queue VARCHAR(255) NOT NULL,
                payload TEXT NOT NULL,
                attempts INT DEFAULT 0,
                reserved INT DEFAULT 0,
                reserved_at INT NULL,
                available_at INT NOT NULL,
                created_at INT NOT NULL
            )',
            self::TABLE
        ));

        return $connection;
    }

    private function mockDoctrineQueue(Connection $connection, int $expire = 60, int $limit = 1): DoctrineQueue
    {
        $jobResolver = $this->createMock(JobResolverInterface::class);
        $test = $this;

        return new class($jobResolver, $connection, self::TABLE, 'default', $expire, $limit, $test) extends DoctrineQueue {
            private DoctrineQueueTest $test;

            public function __construct(
                JobResolverInterface $resolver,
                Connection $connection,
                string $table,
                string $queue,
                int $expire,
                int $limit,
                DoctrineQueueTest $test
            ) {
                parent::__construct($resolver, $connection, $table, $queue, $expire, $limit);
                $this->test = $test;
            }

            protected function currentTime(): int
            {
                return $this->test->now();
            }
        };
    }

    /**
     * Current frozen time, exposed for the anonymous queue subclass.
     */
    public function now(): int
    {
        return $this->now;
    }

    private function fetchSingleRow(Connection $connection): array
    {
        $rows = $connection->fetchAllAssociative(sprintf('SELECT * FROM %s', self::TABLE));

        self::assertCount(1, $rows);

        return $rows[0];
    }

    private function buildJobContract(DoctrineQueue $queue, array $row): JobContract
    {
        $job = new Job();
        $job->setId($row['id']);
        $job->setQueue($row['queue']);
        $job->setAttempts((int)$row['attempts']);
        $job->setPayload(json_decode(trim($row['payload'], "'"), true));

        return new JobContract($this->createMock(JobResolverInterface::class), $queue, $job);
    }
}
