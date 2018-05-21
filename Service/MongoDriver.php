<?php

namespace SfCod\QueueBundle\Service;

use MongoDB\Client;
use MongoDB\Database;
use SfCod\QueueBundle\Base\MongoDriverInterface;

/**
 * Class MongoDriver
 *
 * @author Alexey Orlov <aaorlov88@gmail.com>
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Service
 */
class MongoDriver implements MongoDriverInterface
{
    /**
     * @var string
     */
    protected $dbname;

    /**
     * @var array
     */
    protected $credentials;

    /**
     * @var Client
     */
    protected $client;

    /**
     * Mongodb credentials
     *
     * @param string $uri MongoDB connection string
     * @param array $uriOptions Additional connection string options
     * @param array $driverOptions Driver-specific options
     */
    public function setCredentials(string $uri, array $uriOptions = [], array $driverOptions = [])
    {
        $this->credentials = [
            'uri' => $uri,
            'uriOptions' => $uriOptions,
            'driverOptions' => $driverOptions,
        ];
    }

    /**
     * Get mongodb client
     *
     * @return Client
     */
    public function getClient(): Client
    {
        if (null === $this->client) {
            $this->client = new Client(
                $this->credentials['uri'],
                $this->credentials['uriOptions'],
                $this->credentials['driverOptions']
            );
        }

        return $this->client;
    }

    /**
     * Set client
     *
     * @param MongoDB\Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Set mongo dbname
     *
     * @param string $dbname
     */
    public function setDbname(string $dbname = 'db')
    {
        $this->dbname = $dbname;
    }

    /**
     * Get mongo database
     *
     * @param null|string $name
     *
     * @return Database
     */
    public function getDatabase(?string $name = null): Database
    {
        return $this->getClient()->selectDatabase($name ?? $this->dbname);
    }
}
