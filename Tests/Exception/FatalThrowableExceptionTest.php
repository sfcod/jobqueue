<?php

namespace SfCod\QueueBundle\Tests\Exception;

use Exception;
use PHPUnit\Framework\TestCase;
use SfCod\QueueBundle\Exception\FatalThrowableException;

/**
 * Class FatalThrowableExceptionTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\Exception
 */
class FatalThrowableExceptionTest extends TestCase
{
    /**
     * Test exception
     */
    public function testException()
    {
        $message = uniqid('message_');

        $line = __LINE__ + 1; // Exception line
        $exception = new FatalThrowableException(new Exception($message));

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(__FILE__, $exception->getFile());
        $this->assertEquals($line, $exception->getLine());
    }
}
