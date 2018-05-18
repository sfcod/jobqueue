<?php

namespace SfCod\QueueBundle\Exception;

use InvalidArgumentException;

/**
 * Class InvalidPayloadException
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Exception
 */
class InvalidPayloadException extends InvalidArgumentException
{
    /**
     * Create a new exception instance.
     *
     * @param  string|null $message
     *
     * @return void
     */
    public function __construct($message = null)
    {
        parent::__construct($message ?: json_last_error());
    }
}
