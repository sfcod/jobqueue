<?php

namespace SfCod\QueueBundle\Tests\Data;

use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SfCod\QueueBundle\DependencyInjection\QueueExtension;
use SfCod\QueueBundle\Service\MongoDriver;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Trait LoadTrait
 *
 * @package SfCod\QueueBundle\Tests\Data
 */
trait LoadTrait
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Configure container
     *
     * @throws \ReflectionException
     */
    protected function configure()
    {
        $extension = new QueueExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', '');
        $container->setParameter('kernel.environment', 'prod');
        $container->setParameter('kernel.root_dir', realpath(__DIR__ . '/../../../../SfCod/'));
        $container->set(LoggerInterface::class, new Logger('test'));

        $extension->load([
            0 => [
                'namespaces' => [
                    'SfCod\QueueBundle\Tests\Data',
                ],
            ],
            1 => [
                'connections' => [
                    'default' => [
                        'driver' => 'mongo-thread',
                        'collection' => 'queue_jobs',
                        'connection' => MongoDriver::class,
                        'queue' => 'default',
                        'expire' => 60,
                        'limit' => 2,
                    ],
                ],
            ],
        ], $container);

        $this->container = $container;
    }
}
