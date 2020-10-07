<?php

namespace SfCod\QueueBundle\Command;

use Psr\Log\LoggerInterface;
use SfCod\QueueBundle\Worker\Options;
use SfCod\QueueBundle\Worker\Worker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to run jobs by id
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class RunJobCommand extends Command
{
    /**
     * @var Worker
     */
    protected $worker;

    /**
     * RunJobCommand constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(Worker $worker)
    {
        $this->worker = $worker;

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
            ->addOption('queue', null, InputArgument::OPTIONAL, 'The name of the queue.', 'default')
            ->addOption('delay', null, InputArgument::OPTIONAL, 'Delay before retry failed job.', 0)
            ->addOption('memory', null, InputArgument::OPTIONAL, 'Maximum memory usage limit.', 128)
            ->addOption('sleep', null, InputArgument::OPTIONAL, 'Sleep time before getting new job.', 3)
            ->addOption('maxTries', null, InputArgument::OPTIONAL, 'Max tries to run job.', 1)
            ->addOption('timeout', null, InputArgument::OPTIONAL, 'Daemon timeout.', 60);
    }

    /**
     * Execute command
     *
     * @return int|void|null
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $options = new Options(
            $input->getOption('delay'),
            $input->getOption('memory'),
            $input->getOption('timeout'),
            $input->getOption('sleep'),
            $input->getOption('maxTries')
        );
        $connection = $input->getOption('connection');
        $jobId = $input->getArgument('id');

        $this->worker->runJobById($connection, $jobId, $options);

        return 0;
    }
}
