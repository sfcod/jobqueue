<?php

namespace SfCod\QueueBundle\Tests\Worker;

use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Worker\Options;

/**
 * Class OptionsTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\Worker
 */
class OptionsTest extends TestCase
{
    /**
     * Test worker options
     */
    public function testOptions(): void
    {
        $delay = random_int(1, 100);
        $memory = random_int(128, 2048);
        $timeout = random_int(60, 3600);
        $sleep = random_int(0, 60);
        $maxTries = random_int(1, 10);
        $force = (bool)random_int(0, 1);

        $options = new Options($delay, $memory, $timeout, $sleep, $maxTries, $force);

        self::assertEquals($delay, $options->delay);
        self::assertEquals($memory, $options->memory);
        self::assertEquals($timeout, $options->timeout);
        self::assertEquals($sleep, $options->sleep);
        self::assertEquals($maxTries, $options->maxTries);
        self::assertEquals($force, $options->force);
    }
}
