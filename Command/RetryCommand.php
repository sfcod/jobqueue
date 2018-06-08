<?php

namespace SfCod\QueueBundle\Command;

use SfCod\QueueBundle\Base\MongoDriverInterface;
use SfCod\QueueBundle\Failer\FailedJobProviderInterface;
use SfCod\QueueBundle\Service\JobQueue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ReleaseFailedCommand
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Command
 */
class RetryCommand extends Command
{
    /**
     * @var JobQueue
     */
    protected $queue;

    /**
     * @var FailedJobProviderInterface
     */
    protected $failer;

    /**
     * RetryCommand constructor.
     *
     * @param JobQueue $queue
     * @param MongoDriverInterface $mongoDriver
     */
    public function __construct(JobQueue $queue, FailedJobProviderInterface $failer)
    {
        $this->queue = $queue;
        $this->failer = $failer;

        parent::__construct();
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('job-queue:retry')
            ->setDescription('Release all failed jobs.')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Job id to retry', null);
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $jobsCount = 0;
        if (is_null($input->getOption('id'))) {
            foreach ($this->failer->all() as $job) {
                if ($this->retryJob($job)) {
                    ++$jobsCount;
                }
            }
        } else {
            $job = $this->failer->find($input->getOption('id'));
            $this->retryJob($job);

            ++$jobsCount;
        }

        $io->success(sprintf("[%d] job(s) has been released.\n", $jobsCount));
    }

    /**
     * Retry job
     *
     * @param \stdClass $job
     *
     * @return bool
     */
    protected function retryJob($job): bool
    {
        $payload = json_decode($job->payload, true);

        if ($payload && isset($payload['job'], $payload['data'])) {
            $this->queue->push($payload['job'], $payload['data']);
            $this->failer->forget($job->_id);

            return true;
        }

        return false;
    }
}
