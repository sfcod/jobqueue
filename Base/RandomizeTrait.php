<?php

namespace SfCod\QueueBundle\Base;

/**
 * Trait RandomizeTrait
 *
 * @package SfCod\QueueBundle\Base
 */
trait RandomizeTrait
{
    /**
     * Get a random ID string.
     *
     * @return string
     *
     * @throws \Exception
     */
    private function getRandomId(): string
    {
        return bin2hex(random_bytes(12));
    }
}
