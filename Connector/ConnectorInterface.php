<?php

namespace SfCod\QueueBundle\Connector;

use SfCod\QueueBundle\Queue\QueueInterface;

/**
 * Interface ConnectorInterface
 *
 * @package SfCod\QueueBundle\Connector
 */
interface ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array $config
     *
     * @return QueueInterface
     */
    public function connect(array $config): QueueInterface;
}
