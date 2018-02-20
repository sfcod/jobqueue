<?php

namespace SfCod\QueueBundle\Command;

use Psr\Log\LoggerInterface;
use SfCod\QueueBundle\Failer\MongoFailedJobProvider;
use SfCod\QueueBundle\Handler\ExceptionHandler;
use SfCod\QueueBundle\Options;
use SfCod\QueueBundle\Service\JobQueue;
use SfCod\QueueBundle\Service\MongoDriver;
use SfCod\QueueBundle\Worker;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class WorkCommand
 * Job queue worker. Use pm2 (http://pm2.keymetrics.io/) for fork command.
 *
 *
 * @author Alexey Orlov <aaorlov88@gmail.com>
 *
 * @package SfCod\QueueBundle\Command
 */
class WorkCommand extends ContainerAwareCommand
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
        $this->setName('job-queue:work')
            ->addOption('delay', null, InputArgument::OPTIONAL, 'Delay before getting jobs.', 3)
            ->addOption('memory', null, InputArgument::OPTIONAL, 'Maximum memory usage limit.', 128)
            ->addOption('sleep', null, InputArgument::OPTIONAL, 'Sleep time before getting new job.', 3)
            ->addOption('maxTries', null, InputArgument::OPTIONAL, 'Max tries to run job.', 1)
            ->addOption('timeout', null, InputArgument::OPTIONAL, 'Daemon timeout.', 60)
            ->addOption('connection', null, InputArgument::OPTIONAL, 'The name of the connection.', 'default')
            ->addOption('queue', null, InputArgument::OPTIONAL, 'The name of the queue.', null)
            ->setDescription('Run worker.');
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
        $mongo = $this->getContainer()->get(MongoDriver::class);

        $worker = new Worker(
            $queueManager,
            new MongoFailedJobProvider($mongo, 'queue_jobs_failed'),
            new ExceptionHandler($this->logger)
        );
        $worker->setContainer($this->getContainer());

        $workerOptions = new Options(
            $input->getOption('delay'),
            $input->getOption('memory'),
            $input->getOption('timeout'),
            $input->getOption('sleep'),
            $input->getOption('maxTries')
        );
        $connection = $input->getOption('connection');
        $queue = $input->getOption('queue');

        $worker->daemon($connection, $queue, $workerOptions);
    }
}
