<?php

namespace SfCod\QueueBundle\Exception;

use InvalidArgumentException;

/**
 * Class InvalidPayloadException
 *
 * @author Orlov Alexey <aaorlov88@gmail.com>
 *
 * @package SfCod\QueueBundle\Exception
 */
class JobNotFoundException extends InvalidArgumentException
{
    /**
     * Create a new exception instance.
     *
     * @param string $message
     *
     * @return void
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
