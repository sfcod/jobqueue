<?php

namespace SfCod\QueueBundle\Tests\Worker;

use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Worker\Options;

/**
 * Class OptionsTest
 * @author Virchenko Maksim <muslim1992@gmail.com>
 * @package SfCod\QueueBundle\Tests\Worker
 */
class OptionsTest extends TestCase
{
    /**
     * Test worker options
     */
    public function testOptions()
    {
        $delay = rand(1, 100);
        $memory = rand(128, 2048);
        $timeout = rand(60, 3600);
        $sleep = rand(0, 60);
        $maxTries = rand(1, 10);
        $force = (bool)rand(0, 1);

        $options = new Options($delay, $memory, $timeout, $sleep, $maxTries, $force);

        $this->assertEquals($delay, $options->delay);
        $this->assertEquals($memory, $options->memory);
        $this->assertEquals($timeout, $options->timeout);
        $this->assertEquals($sleep, $options->sleep);
        $this->assertEquals($maxTries, $options->maxTries);
        $this->assertEquals($force, $options->force);
    }
}