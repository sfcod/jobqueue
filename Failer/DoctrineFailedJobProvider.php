<?php

namespace SfCod\QueueBundle\Failer;

use Doctrine\DBAL\Connection;
use Exception;
use Doctrine\DBAL\Driver\Exception as DriverException;
use SfCod\QueueBundle\Entity\Job;

/**
 * Class DoctrineFailedJobProvider
 *
 * CREATE TABLE failed_jobs (
 * id INT AUTO_INCREMENT PRIMARY KEY,
 * connection VARCHAR(255) NOT NULL,
 * queue VARCHAR(255) NOT NULL,
 * payload TEXT NOT NULL,
 * exception TEXT NOT NULL,
 * failed_at INT NOT NULL
 * );
 *
 * A failed job provider implementation using Doctrine DBAL.
 */
class DoctrineFailedJobProvider implements FailedJobProviderInterface
{
    /**
     * The Doctrine DBAL connection instance.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * The name of the table that holds the failed jobs.
     *
     * @var string
     */
    protected $table;

    /**
     * Create a new Doctrine failed job provider.
     *
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(Connection $connection, string $table = 'failed_jobs')
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * Log a failed job into storage.
     *
     * @param string $connection
     * @param string $queue
     * @param string $payload
     * @param Exception $exception
     *
     * @return int|null
     * @throws DriverException
     */
    public function log(string $connection, string $queue, string $payload, Exception $exception)
    {
        $this->connection->insert($this->table, [
            'connection' => $connection,
            'queue' => $queue,
            'payload' => sprintf('\'%s\'', $payload),
            'exception' => $exception->getMessage(),
            'failed_at' => (new \DateTime())->getTimestamp(),
        ]);

        return $this->connection->lastInsertId();
    }

    /**
     * Get a list of all of the failed jobs.
     *
     * @return array
     * @throws DriverException
     */
    public function all()
    {
        $result = [];
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from($this->table)
            ->orderBy('id', 'DESC');

        $jobs = $qb->executeQuery()->fetchAllAssociative();

        foreach ($jobs as $job) {
            $result[] = $this->buildJob($job);
        }

        return $result;
    }

    /**
     * Get a single failed job.
     *
     * @param string $id
     *
     * @return Job|null
     * @throws DriverException
     */
    public function find(string $id)
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
            ->from($this->table)
            ->where('id = :id')
            ->setParameter('id', $id);

        $data = $qb->executeQuery()->fetchAssociative();

        return $data ? $this->buildJob($data) : null;
    }

    /**
     * Delete a single failed job from storage.
     *
     * @param string $id
     *
     * @return bool
     * @throws DriverException
     */
    public function forget(string $id)
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->delete($this->table)
            ->where('id = :id')
            ->setParameter('id', $id);

        return (bool)$qb->executeStatement();
    }

    /**
     * Flush all of the failed jobs from storage.
     *
     * @throws DriverException
     */
    public function flush()
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->delete($this->table)
            ->executeStatement();
    }

    /**
     * Build a job entity from the database record.
     *
     * @param array $data
     *
     * @return Job
     */
    protected function buildJob(array $data)
    {
        $job = new Job();
        $job->setId($data['id']);
        $job->setQueue($data['queue'] ?? 'default');
        $job->setAttempts($data['attempts'] ?? 0);
        $job->setReserved($data['reserved'] ?? 0);
        $job->setReservedAt($data['reserved_at'] ?? null);
        $job->setPayload(json_decode(trim($data['payload'], "'"), true));

        return $job;
    }
}
