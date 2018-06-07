<?php

namespace SfCod\QueueBundle\Tests\Service;

use MongoDB\Client;
use MongoDB\Database;
use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Service\MongoDriver;

/**
 * Class MongoDriverTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\Service
 */
class MongoDriverTest extends TestCase
{
    /**
     * Test mongo driver
     */
    public function testDriver()
    {
        $uri = uniqid('mongodb://');
        $uriOptions = range(1, 10);
        $driverOptions = range(11, 21);
        $dbName = uniqid('db_');

        $driver = new MongoDriver();
        $driver->setCredentials($uri, $uriOptions, $driverOptions);

        $this->assertInstanceOf(Client::class, $driver->getClient());

        $driver->setDbname($dbName);

        $this->assertInstanceOf(Database::class, $driver->getDatabase());
        $this->assertEquals($dbName, $driver->getDatabase()->getDatabaseName());
    }
}
