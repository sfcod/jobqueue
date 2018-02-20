<?php

namespace SfCod\QueueBundle\Base;

use Exception;

/**
 * DaemonExceptionHandler
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class FatalThrowableError extends Exception
{
    /**
     * FatalThrowableError constructor.
     *
     * @param Exception $e
     * @param int $code
     * @param Exception $previous
     */
    public function __construct($e, int $code = 0, Exception $previous = null)
    {
        $this->message = $e->getMessage();
        $this->file = $e->getFile();
        $this->line = $e->getLine();
    }
}
