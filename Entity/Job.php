<?php

namespace SfCod\QueueBundle\Entity;

/**
 * Class Job
 * Job entity, which represents data from any storage/database
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Entity
 */
class Job
{
    /**
     * Unique job id
     *
     * @var string|int
     */
    protected $id;

    /**
     * Job attempts
     *
     * @var int
     */
    protected $attempts;

    /**
     * Name of job's queue
     *
     * @var string
     */
    protected $queue;

    /**
     * Job reserved flag
     *
     * @var bool
     */
    protected $reserved;

    /**
     * Job reserved at time
     *
     * @var int
     */
    protected $reservedAt;

    /**
     * Job's payload data
     *
     * @var array
     */
    protected $payload = [];

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * @param int $attempts
     */
    public function setAttempts(int $attempts)
    {
        $this->attempts = $attempts;
    }

    /**
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * @param string $queue
     */
    public function setQueue(string $queue)
    {
        $this->queue = $queue;
    }

    /**
     * @return bool
     */
    public function isReserved(): bool
    {
        return $this->reserved;
    }

    /**
     * @param bool $reserved
     */
    public function setReserved(bool $reserved)
    {
        $this->reserved = $reserved;
    }

    /**
     * @return int|null
     */
    public function getReservedAt(): ?int
    {
        return $this->reservedAt;
    }

    /**
     * @param int|null $reservedAt
     */
    public function setReservedAt(?int $reservedAt)
    {
        $this->reservedAt = $reservedAt;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param array $payload
     */
    public function setPayload(array $payload)
    {
        $this->payload = $payload;
    }
}
