<?php

namespace SfCod\QueueBundle\Service;

use SfCod\QueueBundle\Job\JobContractInterface;
use SfCod\QueueBundle\Worker\Options;
use Symfony\Component\Process\Process;

/**
 * Class JobProcess
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle
 */
class JobProcess
{
    /**
     * @var string
     */
    public $binPath;

    /**
     * @var string
     */
    public $scriptName;

    /**
     * @var string
     */
    public $binary;

    /**
     * @var string
     */
    public $binaryArgs;

    /**
     * JobProcess constructor.
     *
     * @param string $scriptName
     * @param string $binPath
     * @param string $binary
     * @param string $binaryArgs
     */
    public function __construct(
        string $scriptName,
        string $binPath,
        string $binary = 'php',
        string $binaryArgs = '')
    {
        $this->scriptName = $scriptName;
        $this->binPath = $binPath;
        $this->binary = $binary;
        $this->binaryArgs = $binaryArgs;
    }

    /**
     * Get the Artisan process for the job id.
     *
     * @param JobContractInterface $job
     * @param Options $options
     *
     * @return Process
     */
    public function getProcess(JobContractInterface $job, Options $options): Process
    {
        $cmd = '%s %s job-queue:run-job %s --connection=%s --queue=%s --env=%s --delay=%s --memory=%s --timeout=%s --sleep=%s --maxTries=%s';
        $cmd = $this->getBackgroundCommand($cmd);
        $cmd = sprintf(
            $cmd,
            $this->getPhpBinary(),
            $this->scriptName,
            $job->getJobId(),
            $job->getConnectionName(),
            $job->getQueue(),
            getenv('APP_ENV'),
            $options->delay,
            $options->memory,
            $options->timeout,
            $options->sleep,
            $options->maxTries
        );

        return new Process(explode(' ', $cmd), $this->binPath);
    }

    /**
     * @param $cmd
     *
     * @return string
     */
    protected function getBackgroundCommand(string $cmd): string
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            return 'start /B ' . $cmd . ' > NUL';
        }

        return $cmd . ' > /dev/null 2>&1 &';
    }

    /**
     * Get the escaped PHP Binary from the configuration
     *
     * @return string
     */
    protected function getPhpBinary(): string
    {
        $path = $this->binary;
        if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            $path = escapeshellarg($path);
        }

        $args = $this->binaryArgs;
        if (is_array($args)) {
            $args = implode(' ', $args);
        }

        return trim(trim($path . ' ' . $args), '\'');
    }
}
