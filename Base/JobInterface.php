<?php

namespace SfCod\QueueBundle\Base;

use SfCod\QueueBundle\JobContract\JobContractInterface;

/**
 * Base interface for handlers
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
interface JobInterface
{
    /**
     * Run command from queue
     *
     * @param JobContractInterface $job
     * @param array $data
     */
    public function fire(JobContractInterface $job, array $data);
}
