<?php

namespace SfCod\QueueBundle\Service;

use InvalidArgumentException;
use SfCod\QueueBundle\Connector\ConnectorInterface;
use SfCod\QueueBundle\Queue\QueueInterface;

/**
 * Class QueueManager
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Service
 */
class QueueManager
{
    /**
     * Queue config
     *
     * @var array
     */
    protected $config = [
        'queue.default' => 'default',
    ];

    /**
     * The array of resolved queue connections.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * The array of resolved queue connectors.
     *
     * @var array
     */
    protected $connectors = [];

    /**
     * Determine if the driver is connected.
     *
     * @param string $name
     *
     * @return bool
     */
    public function connected(?string $name = null): bool
    {
        return isset($this->connections[$name ?: $this->getDefaultDriver()]);
    }

    /**
     * Resolve a queue connection instance.
     *
     * @param string $name
     *
     * @return QueueInterface
     */
    public function connection(?string $name = null): QueueInterface
    {
        $name = $name ?? $this->getDefaultDriver();

        // If the connection has not been resolved yet we will resolve it now as all
        // of the connections are resolved when they are actually needed so we do
        // not make any unnecessary connection to the various queue end-points.
        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->resolve($name);
        }

        return $this->connections[$name];
    }

    /**
     * Add a queue connection resolver.
     *
     * @param string $driver
     * @param ConnectorInterface $resolver
     *
     * @return void
     */
    public function addConnector(string $driver, $resolver)
    {
        $this->connectors[$driver] = $resolver;
    }

    /**
     * Register a connection with the manager.
     *
     * @param array $config
     * @param string $name
     *
     * @return void
     */
    public function addConnection(array $config, ?string $name = null)
    {
        $name = $name ?? $this->getDefaultDriver();

        $this->config["queue.connections.{$name}"] = $config;
    }

    /**
     * Get the name of the default queue connection.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->config['queue.default'];
    }

    /**
     * Set the name of the default queue connection.
     *
     * @param string $name
     *
     * @return void
     */
    public function setDefaultDriver(string $name)
    {
        $this->config['queue.default'] = $name;
    }

    /**
     * Get the full name for the given connection.
     *
     * @param string $connection
     *
     * @return string
     */
    public function getName(?string $connection = null): string
    {
        return $connection ?? $this->getDefaultDriver();
    }

    /**
     * Dynamically pass calls to the default connection.
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }

    /**
     * Get the queue connection configuration.
     *
     * @param string $name
     *
     * @return array
     */
    protected function getConfig(string $name): array
    {
        if (!is_null($name) && 'null' !== $name) {
            return $this->config["queue.connections.{$name}"];
        }

        return ['driver' => 'null'];
    }

    /**
     * Resolve a queue connection.
     *
     * @param string $name
     *
     * @return QueueInterface
     */
    protected function resolve(string $name): QueueInterface
    {
        $config = $this->getConfig($name);

        return $this->getConnector($config['driver'])
            ->connect($config)
            ->setConnectionName($name);
    }

    /**
     * Get the connector for a given driver.
     *
     * @param string $driver
     *
     * @return ConnectorInterface
     *
     * @throws InvalidArgumentException
     */
    protected function getConnector(string $driver): ConnectorInterface
    {
        if (!isset($this->connectors[$driver])) {
            throw new InvalidArgumentException("No connector for [$driver]");
        }

        return $this->connectors[$driver];
    }
}
