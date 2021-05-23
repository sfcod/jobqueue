<?php

namespace SfCod\QueueBundle\Service;

use Predis\Client;

/**
 * Class RedisDriver
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Service
 */
class RedisDriver
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $uri;

    /**
     * RedisDriver constructor.
     *
     * @param string $uri
     */
    public function __construct(string $uri)
    {
        $this->uri = $uri;
    }

    /**
     * Get redis client
     *
     * @return Client
     */
    public function getClient(): Client
    {
        if (null === $this->client) {
            $this->client = new Client($this->uri);
        }

        return $this->client;
    }
}
