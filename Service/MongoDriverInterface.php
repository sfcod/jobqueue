<?php

namespace SfCod\QueueBundle\Service;

use MongoDB\Database;

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
