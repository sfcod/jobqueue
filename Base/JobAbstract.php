<?php

namespace SfCod\QueueBundle\Base;

use Doctrine\DBAL\Connection;
use Illuminate\Queue\Jobs\Job;

/**
 * Handler for queue jobs
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
abstract class JobAbstract implements JobInterface
{
    /**
     * Run job with restarting connection
     *
     * @param Job $job
     * @param array $data
     *
     * @throws \Illuminate\Container\EntryNotFoundException
     */
    public function fire(Job $job, array $data)
    {
        /** @var Connection $connection */
        $connection = $job->getContainer()->get('doctrine')->getConnection();
        $connection->close();
        $connection->connect();
    }
}
