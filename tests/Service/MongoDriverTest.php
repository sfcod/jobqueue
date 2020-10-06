<?php

namespace SfCod\QueueBundleTests\Service;

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
    public function testDriver(): void
    {
        $uri = uniqid('mongodb://', true);
        $uriOptions = range(1, 10);
        $driverOptions = range(11, 21);
        $dbName = uniqid('db_', true);

        $driver = new MongoDriver();
        $driver->setCredentials($uri, $uriOptions, $driverOptions);

        self::assertInstanceOf(Client::class, $driver->getClient());

        $driver->setDbname($dbName);

        self::assertInstanceOf(Database::class, $driver->getDatabase());
        self::assertEquals($dbName, $driver->getDatabase()->getDatabaseName());
    }
}
