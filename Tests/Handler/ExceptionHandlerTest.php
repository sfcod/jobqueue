<?php

namespace SfCod\QueueBundle\Tests\Handler;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SfCod\QueueBundle\Handler\ExceptionHandler;

/**
 * Class ExceptionHandlerTest
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Tests\Handler
 */
class ExceptionHandlerTest extends TestCase
{
    /**
     * Test handler report
     */
    public function testReport(): void
    {
        $message = uniqid('message_', true);

        $exception = new Exception($message);
        $logger = $this->mockLogger($exception);
        $handler = $this->mockHandler($logger);

        $handler->report($exception);

        self::assertTrue(true);
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
            ->method('error')
            ->with(self::equalTo($exception->getMessage()), self::equalTo(['exception' => $exception]));

        return $logger;
    }

    /**
     * Mock exception handler
     *
     * @param LoggerInterface $logger
     * @return ExceptionHandler
     */
    private function mockHandler(LoggerInterface $logger): ExceptionHandler
    {
        /** @var ExceptionHandler $handler */
        $handler = $this->getMockBuilder(ExceptionHandler::class)
            ->setConstructorArgs([$logger])
            ->setMethods(null)
            ->getMock();

        return $handler;
    }
}
