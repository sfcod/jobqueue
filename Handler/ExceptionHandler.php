<?php

namespace SfCod\QueueBundle\Handler;

use Exception;
use Psr\Log\LoggerInterface;

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
    public function report(Exception $e)
    {
        $this->logger->error($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \HttpRequest $request
     * @param \Exception $e
     *
     * @return \Symfony\Component\HttpFoundation\Response|void
     */
    public function render($request, Exception $e)
    {
        return;
    }

    /**
     * Render an exception to the console.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Exception $e
     */
    public function renderForConsole($output, Exception $e)
    {
        return;
    }
}
