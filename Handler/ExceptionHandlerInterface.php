<?php

namespace SfCod\QueueBundle\Handler;

use Exception;

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
    public function report(Exception $e);

    /**
     * Render an exception into an HTTP response.
     *
     * @param $request
     * @param Exception $e
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $e);

    /**
     * Render an exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @param  Exception $e
     *
     * @return void
     */
    public function renderForConsole($output, Exception $e);
}
