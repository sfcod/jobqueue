<?php

namespace SfCod\QueueBundle\Service;

use MongoDB\Client;
use MongoDB\Database;

/**
 * Class MongoDriver
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Service
 */
class MongoDriver
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $dbname;

    /**
     * @var Client
     */
    protected $client;

    /**
     * MongoConnection constructor.
     *
     * @param string $host
     * @param string $dbname
     */
    public function __construct(string $host = 'mongodb://localhost:27017', string $dbname = 'db')
    {
        $this->host = $host;
        $this->dbname = $dbname;
    }

    /**
     * Get mongo client
     *
     * @param bool $reset
     *
     * @return Client
     */
    public function getClient(bool $reset = false): Client
    {
        if (is_null($this->client) || $reset) {
            $this->client = new Client($this->host);
        }

        return $this->client;
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
