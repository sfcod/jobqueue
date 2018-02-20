<?php

namespace SfCod\QueueBundle\Base;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Job
 *
 * @author Alexey Orlov <aaorlov88@gmail.com>
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Base
 */
abstract class Job extends \Illuminate\Queue\Jobs\Job
{
    /**
     * The IoC container instance.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Resolve the given class.
     *
     * @param  string $class
     *
     * @return mixed
     */
    protected function resolve($class)
    {
        return $this->container->get($class);
    }

    /**
     * Get job attempts
     *
     * @return int
     */
    abstract public function attempts(): int;

    /**
     * Get is job reserved
     *
     * @return bool
     */
    abstract public function reserved(): bool;
}
