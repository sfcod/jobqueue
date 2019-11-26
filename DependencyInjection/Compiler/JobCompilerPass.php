<?php

namespace SfCod\QueueBundle\DependencyInjection\Compiler;

use SfCod\QueueBundle\Service\JobQueue;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class JobCompilerPass
 * @author Virchenko Maksim <muslim1992@gmail.com>
 * @package SfCod\QueueBundle\DependencyInjection\Compiler
 */
class JobCompilerPass implements CompilerPassInterface
{
    /**
     * Find all job handlers and mark them as public in case to work properly with job queue
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(JobQueue::class)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds('sfcod.jobqueue.job');

        foreach ($taggedServices as $id => $tags) {
            $definition = $container->findDefinition($id);
            $definition->setPublic(true);
        }
    }
}