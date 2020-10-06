<?php

namespace SfCod\QueueBundle\Base;

use MongoDB\Database;

/**
 * Interface MongoDriverInterface
 *
 * @package SfCod\QueueBundle\Service
 */
interface MongoDriverInterface
{
    /**
     * Get mongo database
     *
     * @param null|string $name
     *
     * @return Database
     */
    public function getDatabase(?string $name = null): Database;
}
