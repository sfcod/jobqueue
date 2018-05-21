<?php

namespace SfCod\QueueBundle\Service;

use SfCod\QueueBundle\Base\JobInterface;
use SfCod\QueueBundle\Base\JobResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
     * @var ContainerInterface
     */
    protected $container;

    /**
     * JobResolver constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Resolve the given class.
     *
     * @param string $class
     *
     * @return JobInterface
     */
    public function resolve(string $class): JobInterface
    {
        return $this->container->get($class);
    }
}
