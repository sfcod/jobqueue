<?php

namespace SfCod\QueueBundle\Base;

/**
 * Interface JobResolverInterface
 *
 * @package SfCod\QueueBundle\Service
 */
interface JobResolverInterface
{
    /**
     * Resolve job by id
     *
     * @param string $id
     *
     * @return JobInterface
     */
    public function resolve(string $id): JobInterface;

    /**
     * Add new job
     *
     * @param string $id
     * @param JobInterface $job
     */
    public function addJob(string $id, JobInterface $job);
}
