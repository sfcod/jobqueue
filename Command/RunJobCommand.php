<?php

namespace SfCod\QueueBundle\Command;

use Psr\Log\LoggerInterface;
use SfCod\QueueBundle\Failer\MongoFailedJobProvider;
use SfCod\QueueBundle\Handler\ExceptionHandler;
use SfCod\QueueBundle\Options;
use SfCod\QueueBundle\Service\JobQueue;
use SfCod\QueueBundle\Service\MongoDriverInterface;
use SfCod\QueueBundle\Worker;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to run jobs by id
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class RunJobCommand extends ContainerAwareCommand
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('job-queue:run-job')
            ->setDescription('Runs single job by id.')
            ->addArgument('id', InputArgument::REQUIRED, 'The id of the job.')
            ->addOption('connection', null, InputArgument::OPTIONAL, 'The name of the connection.', 'default')
            ->addOption('queue', null, InputArgument::OPTIONAL, 'The name of the queue.', null)
            ->addOption('delay', null, InputArgument::OPTIONAL, 'Delay before getting jobs.', 0)
            ->addOption('memory', null, InputArgument::OPTIONAL, 'Maximum memory usage limit.', 128)
            ->addOption('sleep', null, InputArgument::OPTIONAL, 'Sleep time before getting new job.', 3)
            ->addOption('maxTries', null, InputArgument::OPTIONAL, 'Max tries to run job.', 1)
            ->addOption('timeout', null, InputArgument::OPTIONAL, 'Daemon timeout.', 60);
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
        $queueManager = $this->getContainer()->get(JobQueue::class)->getQueueManager();
        $mongo = $this->getContainer()->get(MongoDriverInterface::class);

        $worker = new Worker(
            $queueManager,
            new MongoFailedJobProvider($mongo, 'queue_jobs_failed'),
            new ExceptionHandler($this->logger)
        );
        $worker->setContainer($this->getContainer());

        $worker->runJobById($input->getOption('connection'), $input->getArgument('id'), new Options(
            $input->getOption('delay'),
            $input->getOption('memory'),
            $input->getOption('timeout'),
            $input->getOption('sleep'),
            $input->getOption('maxTries')
        ));
    }
}
