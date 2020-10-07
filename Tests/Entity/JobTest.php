<?php

namespace SfCod\QueueBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Entity\Job;

/**
 * Class JobTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\Entity
 */
class JobTest extends TestCase
{
    /**
     * Test job
     */
    public function testJob()
    {
        $id = rand(1, 99999);
        $attempts = rand(1, 10);
        $queue = uniqid('queue_');
        $reserved = (bool)rand(0, 1);
        $reservedAt = time();
        $payload = range(1, 100);

        $job = new Job();
        $job->setId($id);
        $job->setAttempts($attempts);
        $job->setQueue($queue);
        $job->setReserved($reserved);
        $job->setReservedAt($reservedAt);
        $job->setPayload($payload);

        self::assertEquals($id, $job->getId());
        self::assertEquals($attempts, $job->getAttempts());
        self::assertEquals($queue, $job->getQueue());
        self::assertEquals($reserved, $job->isReserved());
        self::assertEquals($reservedAt, $job->getReservedAt());
        self::assertEquals($payload, $job->getPayload());
    }
}
