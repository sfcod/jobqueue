<?php

namespace SfCod\QueueBundle\Command;

use SfCod\QueueBundle\Worker\Options;
use SfCod\QueueBundle\Worker\Worker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class WorkCommand
 * Job queue worker. Use pm2 (http://pm2.keymetrics.io/) for fork command.
 *
 * @author Alexey Orlov <aaorlov88@gmail.com>
 *
 * @package SfCod\QueueBundle\Command
 */
class WorkCommand extends Command
{
    /**
     * @var Worker
     */
    protected $worker;

    /**
     * WorkCommand constructor.
     *
     * @param Worker $worker
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
        $this->setName('job-queue:work')
            ->addOption('delay', null, InputArgument::OPTIONAL, 'Delay before getting jobs.', 3)
            ->addOption('memory', null, InputArgument::OPTIONAL, 'Maximum memory usage limit.', 128)
            ->addOption('sleep', null, InputArgument::OPTIONAL, 'Sleep time before getting new job.', 3)
            ->addOption('maxTries', null, InputArgument::OPTIONAL, 'Max tries to run job.', 1)
            ->addOption('timeout', null, InputArgument::OPTIONAL, 'Daemon timeout.', 60)
            ->addOption('connection', null, InputArgument::OPTIONAL, 'The name of the connection.', 'default')
            ->addOption('queue', null, InputArgument::OPTIONAL, 'The name of the queue.', 'default')
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
        $io = new SymfonyStyle($input, $output);

        $workerOptions = new Options(
            $input->getOption('delay'),
            $input->getOption('memory'),
            $input->getOption('timeout'),
            $input->getOption('sleep'),
            $input->getOption('maxTries')
        );
        $connection = $input->getOption('connection');
        $queue = $input->getOption('queue');

        $io->success(sprintf('Worker daemon has started.'));

        $this->worker->daemon($connection, $queue, $workerOptions);
    }
}
