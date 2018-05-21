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
     * Resolve job by class
     *
     * @param string $class
     *
     * @return JobInterface
     */
    public function resolve(string $class): JobInterface;
}
