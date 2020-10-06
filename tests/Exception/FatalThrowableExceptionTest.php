<?php

namespace SfCod\QueueBundleTests\Exception;

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
    public function testException(): void
    {
        $message = uniqid('message_', true);

        $line = __LINE__ + 1; // Exception line
        $exception = new FatalThrowableException(new Exception($message));

        self::assertEquals($message, $exception->getMessage());
        self::assertEquals(__FILE__, $exception->getFile());
        self::assertEquals($line, $exception->getLine());
    }
}
