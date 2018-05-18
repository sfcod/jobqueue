<?php

namespace SfCod\QueueBundle\Base;

use SfCod\QueueBundle\Job\JobContract;

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
     * @param JobContract $job
     * @param array $data
     */
    public function fire(JobContract $job, array $data);
}
