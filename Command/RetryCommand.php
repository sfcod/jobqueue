<?php

namespace SfCod\QueueBundle\Command;

use SfCod\QueueBundle\Entity\Job;
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
     * @param FailedJobProviderInterface $failer
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
            ->setDescription('Release failed job(s).')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Job id to retry, if not set all failed jobs will be affected.', null);
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
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

            if ($job) {
                $this->retryJob($job);

                ++$jobsCount;
            }
        }

        $io->success(sprintf("[%d] job(s) has been released.\n", $jobsCount));

        return 0;
    }

    /**
     * Retry job
     *
     * @param Job $job
     *
     * @return bool
     */
    protected function retryJob(Job $job): bool
    {
        $payload = $job->getPayload();

        if ($payload && isset($payload['job'], $payload['data'])) {
            $this->queue->push($payload['job'], $payload['data'], $job->getQueue() ?? 'default');
            $this->failer->forget($job->getId());

            return true;
        }

        return false;
    }
}
