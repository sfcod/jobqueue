<?php

namespace SfCod\QueueBundle\Service;

use SfCod\QueueBundle\Base\JobInterface;
use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Exception\JobNotFoundException;

/**
 * Class JobResolver
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Service
 */
class JobResolver implements JobResolverInterface
{
    /**
     * @var JobInterface[]
     */
    private $jobs = [];

    /**
     * Resolve the given class.
     *
     * @param string $id
     *
     * @return JobInterface
     */
    public function resolve(string $id): JobInterface
    {
        if (isset($this->jobs[$id])) {
            return $this->jobs[$id];
        }

        throw new JobNotFoundException("Job handler '$id' not found.");
    }

    /**
     * @inheritDoc
     *
     * @param string $id
     * @param JobInterface $job
     */
    public function addJob(string $id, JobInterface $job)
    {
        $this->jobs[$id] = $job;
    }
}
