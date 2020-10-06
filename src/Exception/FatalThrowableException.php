<?php

namespace SfCod\QueueBundle\Exception;

use Exception;

/**
 * DaemonExceptionHandler
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class FatalThrowableException extends Exception
{
    /**
     * FatalThrowableException constructor.
     *
     * @param Exception $e
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(Exception $e, int $code = 0, Exception $previous = null)
    {
        $this->message = $e->getMessage();
        $this->file = $e->getFile();
        $this->line = $e->getLine();
    }
}
