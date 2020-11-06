<?php

namespace SfCod\QueueBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Predis\Client;
use SfCod\QueueBundle\Service\RedisDriver;

/**
 * Class RedisDriverTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\Service
 */
class RedisDriverTest extends TestCase
{
    /**
     * Test mongo driver
     */
    public function testDriver()
    {
        $uri = uniqid('redis://redis');

        $driver = new RedisDriver($uri);

        self::assertInstanceOf(Client::class, $driver->getClient());
    }
}
