<?php

namespace SfCod\QueueBundle\Queue;

use DateInterval;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Entity\Job;
use SfCod\QueueBundle\Job\JobContract;
use SfCod\QueueBundle\Job\JobContractInterface;

/**
 * Class DoctrineQueue
 *
 * A queue implementation using Doctrine DBAL.
 *
 * CREATE TABLE queue_jobs (
 * id INT AUTO_INCREMENT PRIMARY KEY,
 * queue VARCHAR(255) NOT NULL,
 * payload TEXT NOT NULL,
 * attempts INT DEFAULT 0,
 * reserved INT DEFAULT 0,
 * reserved_at INT NULL,
 * available_at INT NOT NULL,
 * created_at INT NOT NULL
 * );
 *
 * @package SfCod\QueueBundle\Queue
 */
class DoctrineQueue extends Queue
{
    protected $resolver;
    protected $connection;
    protected $table;
    protected $queue = 'default';
    protected $expire = 60;
    protected $limit = 1;

    public function __construct(
        JobResolverInterface $resolver,
        Connection $connection,
        string $table,
        string $queue = 'default',
        int $expire = 60,
        int $limit = 1
    ) {
        $this->resolver = $resolver;
        $this->connection = $connection;
        $this->table = $table;
        $this->queue = $queue;
        $this->expire = $expire;
        $this->limit = $limit;
    }

    public function push(string $job, array $data = [], ?string $queue = null)
    {
        return $this->pushToDatabase(0, $queue, $this->createPayload($job, $data));
    }

    public function pop(?string $queue = null): ?JobContractInterface
    {
        $queue = $this->getQueue($queue);

        if ($job = $this->getNextAvailableJob($queue)) {
            return $job;
        }

        return null;
    }

    public function pushRaw(string $payload, ?string $queue = null, array $options = [])
    {
        return $this->pushToDatabase(0, $queue, $payload);
    }

    public function later($delay, string $job, array $data = [], ?string $queue = null)
    {
        return $this->pushToDatabase($delay, $queue, $this->createPayload($job, $data));
    }

    public function bulk(array $jobs, $data = '', ?string $queue = null)
    {
        foreach ($jobs as $job) {
            $this->push($job, $data, $queue);
        }
    }

    public function release(JobContractInterface $job, $delay)
    {
        return $this->pushToDatabase($delay, $job->getQueue(), sprintf('\'%s\'', json_encode($job->payload())), $job->attempts());
    }

    public function exists(string $job, array $data = [], ?string $queue = null): bool
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('COUNT(*)')
            ->from($this->table)
            ->where('queue = :queue')
            ->andWhere('payload = :payload')
            ->setParameter('queue', $this->getQueue($queue))
            ->setParameter('payload', $this->createPayload($job, $data));

        return (bool)$qb->executeQuery()->fetchOne();
    }

    protected function createPayload(string $job, array $data = [])
    {
        $payload = parent::createPayload($job, $data);

        return sprintf('\'%s\'', $payload);
    }

    public function size(?string $queue = null): int
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('COUNT(*)')
            ->from($this->table)
            ->where('queue = :queue')
            ->setParameter('queue', $this->getQueue($queue));

        return (int)$qb->executeQuery()->fetchOne();
    }

    public function deleteReserved(string $queue, string $id): bool
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->delete($this->table)
            ->where('queue = :queue')
            ->andWhere('id = :id')
            ->setParameter('queue', $queue)
            ->setParameter('id', $id)
            ->executeStatement();

        return true;
    }

    public function getJobById(string $queue, string $id): ?JobContractInterface
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from($this->table)
            ->where('id = :id')
            ->setParameter('id', $id);

        $job = $qb->executeQuery()->fetchAssociative();

        return $job ? new JobContract($this->resolver, $this, $this->buildJob($job)) : null;
    }

    public function canRunJob(JobContractInterface $job): bool
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('COUNT(id)')
            ->from($this->table)
            ->where('queue = :queue')
            ->andWhere('reserved = :reserved')
            ->setParameter('reserved', 1)
            ->setParameter('queue', $job->getQueue());

        $reserved = $qb->executeQuery()->fetchOne();

        return $reserved < $this->limit || $job->reserved();
    }

    protected function getNextAvailableJob(string $queue): ?JobContractInterface
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from($this->table)
            ->where('queue = :queue')
            ->andWhere('reserved_at IS NULL OR reserved_at <= :expired_at')
            ->setParameter('queue', $queue)
            ->setParameter('expired_at', $this->currentTime() - $this->expire)
            ->setMaxResults(1)
            ->orderBy('id', 'ASC');

        $job = $qb->executeQuery()->fetchAssociative();
        return $job ? new JobContract($this->resolver, $this, $this->buildJob($job)) : null;
    }

    /**
     * Build job from database record
     *
     * @param $data
     *
     * @return Job
     */
    protected function buildJob($data): Job
    {
        $job = new Job();
        $job->setId($data['id']);
        $job->setAttempts($data['attempts']);
        $job->setQueue($data['queue']);
        $job->setReserved($data['reserved']);
        $job->setReservedAt($data['reserved_at']);
        $job->setPayload(json_decode(trim($data['payload'], "'"), true));

        return $job;
    }

    protected function pushToDatabase($delay, $queue, $payload, $attempts = 0)
    {
        $attributes = $this->buildDatabaseRecord($this->getQueue($queue), $payload, $this->getAvailableAt($delay), $attempts);

        $this->connection->insert($this->table, $attributes);

        return $this->connection->lastInsertId();
    }

    protected function buildDatabaseRecord($queue, $payload, $availableAt, $attempts = 0)
    {
        return [
            'queue' => $queue,
            'payload' => $payload,
            'attempts' => $attempts,
            'reserved' => 0,
            'reserved_at' => null,
            'available_at' => $availableAt,
            'created_at' => $this->currentTime(),
        ];
    }

    public function markJobAsReserved(JobContractInterface $job)
    {
        $qb = $this->connection->createQueryBuilder();

        $qb->update($this->table)
            ->set('reserved', 1)
            ->set('reserved_at', $this->currentTime())
            ->set('attempts', $job->attempts() + 1)
            ->where('id = :id')
            ->setParameter('id', $job->getJobId())
            ->executeStatement();
    }

    protected function getQueue(?string $queue)
    {
        return $queue ?: $this->queue;
    }

    protected function getAvailableAt($delay)
    {
        return $delay instanceof DateInterval
            ? (new DateTime())->add($delay)->getTimestamp()
            : $this->currentTime() + $delay;
    }
}
