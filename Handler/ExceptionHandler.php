<?php

namespace SfCod\QueueBundle\Handler;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * ExceptionHandler
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class ExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ExceptionHandler constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Report or log an exception.
     *
     * @param \Exception $e
     */
    public function report(Exception $e): void
    {
        $this->logger->error($e->getMessage(), ['exception' => $e]);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param HttpRequest $request
     * @param \Exception $e
     *
     * @return Response
     */
    public function render($request, Exception $e): Response
    {
    }

    /**
     * Render an exception to the console.
     *
     * @param OutputInterface $output
     * @param Exception $e
     */
    public function renderForConsole(OutputInterface $output, Exception $e): void
    {
    }
}
