<?php

namespace SfCod\QueueBundle;

use SfCod\QueueBundle\DependencyInjection\Compiler\JobCompilerPass;
use SfCod\QueueBundle\DependencyInjection\QueueExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class CommonQueueBundle
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle
 */
class SfCodQueueBundle extends Bundle
{
    /**
     * Get bundle extension
     *
     * @return null|QueueExtension|\Symfony\Component\DependencyInjection\Extension\ExtensionInterface
     */
    public function getContainerExtension()
    {
        return new QueueExtension();
    }

    /**
     * Add compiler pass
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new JobCompilerPass());
    }
}
