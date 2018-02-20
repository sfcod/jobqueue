<?php

namespace SfCod\QueueBundle;

use SfCod\QueueBundle\DependencyInjection\QueueExtension;
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
}
