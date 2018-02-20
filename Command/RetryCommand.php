<?php

namespace SfCod\QueueBundle\Command;

use SfCod\QueueBundle\Failer\MongoFailedJobProvider;
use SfCod\QueueBundle\Service\JobQueue;
use SfCod\QueueBundle\Service\MongoDriver;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ReleaseFailedCommand
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\Command
 */
class RetryCommand extends ContainerAwareCommand
{
    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('job-queue:retry')
            ->setDescription('Release all failed jobs.');
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

        $queue = $this->getContainer()->get(JobQueue::class);
        $mongo = $this->getContainer()->get(MongoDriver::class);
        $failer = new MongoFailedJobProvider($mongo, 'queue_jobs_failed');

        $jobsCount = 0;
        foreach ($failer->all() as $job) {
            $payload = json_decode($job->payload, true);

            if ($payload && isset($payload['job'], $payload['data'])) {
                $queue->push($payload['job'], $payload['data']);
                $failer->forget($job->_id);

                ++$jobsCount;

//                $io->writeln(sprintf("Job [%s] has been released.\n", $job->_id));
            }
        }

        $io->success(sprintf("[%d] job(s) has been released.\n", $jobsCount));
    }
}
