<?php

namespace SfCod\QueueBundle\Tests\Handler;

use PHPUnit\Framework\TestCase;
use Exception;
use Psr\Log\LoggerInterface;
use SfCod\QueueBundle\Handler\ExceptionHandler;

/**
 * Class ExceptionHandlerTest
 * @author Virchenko Maksim <muslim1992@gmail.com>
 * @package SfCod\QueueBundle\Tests\Handler
 */
class ExceptionHandlerTest extends TestCase
{
    /**
     * Test handler report
     */
    public function testReport()
    {
        $message = uniqid('message_');

        $exception = new Exception($message);
        $logger = $this->mockLogger($exception);
        $handler = $this->mockHandler($logger);

        $handler->report($exception);
    }

    /**
     * Mock logger
     *
     * @param Exception $exception
     *
     * @return LoggerInterface
     */
    private function mockLogger(Exception $exception): LoggerInterface
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with($this->equalTo($exception->getMessage()), $this->equalTo(['exception' => $exception]));

        return $logger;
    }

    /**
     * Mock exception handler
     *
     * @return ExceptionHandler
     */
    private function mockHandler(LoggerInterface $logger): ExceptionHandler
    {
        $handler = $this->getMockBuilder(ExceptionHandler::class)
            ->setConstructorArgs([$logger])
            ->setMethods(null)
            ->getMock();

        return $handler;
    }
}