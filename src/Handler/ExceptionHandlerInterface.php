<?php

namespace SfCod\QueueBundle\Handler;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface ExceptionHandlerInterface
 *
 * @package SfCod\QueueBundle\Handler
 */
interface ExceptionHandlerInterface
{
    /**
     * Report or log an exception.
     *
     * @param Exception $e
     *
     * @return void
     */
    public function report(Exception $e): void;

    /**
     * Render an exception into an HTTP response.
     *
     * @param $request
     * @param Exception $e
     *
     * @return Response
     */
    public function render($request, Exception $e): Response;

    /**
     * Render an exception to the console.
     *
     * @param  OutputInterface $output
     * @param  Exception $e
     *
     * @return void
     */
    public function renderForConsole(OutputInterface $output, Exception $e): void;
}
