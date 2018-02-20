<?php

namespace SfCod\QueueBundle;

/**
 * Class WorkerOptions
 *
 * @package SfCod\QueueBundle
 */
class Options
{
    /**
     * The number of seconds before a released job will be available.
     *
     * @var int
     */
    public $delay;

    /**
     * The maximum amount of RAM the worker may consume.
     *
     * @var int
     */
    public $memory;

    /**
     * The maximum number of seconds a child worker may run.
     *
     * @var int
     */
    public $timeout;

    /**
     * The number of seconds to wait in between polling the queue.
     *
     * @var int
     */
    public $sleep;

    /**
     * The maximum amount of times a job may be attempted.
     *
     * @var int
     */
    public $maxTries;

    /**
     * Indicates if the worker should run in maintenance mode.
     *
     * @var bool
     */
    public $force;

    /**
     * WorkerOptions constructor.
     *
     * @param int $delay
     * @param int $memory
     * @param int $timeout
     * @param int $sleep
     * @param int $maxTries
     * @param bool $force
     */
    public function __construct(
        int $delay = 0,
        int $memory = 128,
        int $timeout = 60,
        int $sleep = 3,
        int $maxTries = 0,
        bool $force = false
    ) {
        $this->delay = $delay;
        $this->sleep = $sleep;
        $this->force = $force;
        $this->memory = $memory;
        $this->timeout = $timeout;
        $this->maxTries = $maxTries;
    }

    public function getBinaryArgs(): string
    {
        return getenv('BINARY_ARGS') ?? '';
    }

    public function getBinPath()
    {
        return getenv('BIN_PATH');
    }

    public function getScriptName(): string
    {
        return getenv('SCRIPT_NAME');
    }

    /**
     * Get the escaped PHP Binary from the configuration
     *
     * @return string
     */
    public function getPhpBinary(): string
    {
        $path = $this->binary;
        if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            $path = escapeshellarg($path);
        }

        $args = $this->binaryArgs;
        if (is_array($args)) {
            $args = implode(' ', $args);
        }

        return trim($path . ' ' . $args);
    }
}
