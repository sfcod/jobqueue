<?php

namespace SfCod\QueueBundle\DependencyInjection;

use Psr\Log\LoggerInterface;
use SfCod\QueueBundle\Command\RetryCommand;
use SfCod\QueueBundle\Command\RunJobCommand;
use SfCod\QueueBundle\Command\WorkCommand;
use SfCod\QueueBundle\JobProcess;
use SfCod\QueueBundle\Service\JobQueue;
use SfCod\QueueBundle\Service\MongoDriver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Glob;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class SfcodQueueExtension
 *
 * @author Alexey Orlov <aaorlov88@gmail.com>
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\DependencyInjection
 */
class QueueExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array $configs
     * @param ContainerBuilder $container
     *
     * @throws \ReflectionException
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new QueueConfiguration();

        $config = $this->processConfiguration($configuration, $configs);

        $jobs = $this->grabJobs($container);

        foreach ($jobs as $job) {
            $definition = new Definition($job);
            $definition
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setPublic(true);
            $container->setDefinition($job, $definition);
        }
        $this->createDriver($config, $container);
        $this->createJobQueue($config, $container);
        $this->createJobProcess($config, $container);
        $this->createCommands($config, $container);
    }

    /**
     * Get extension alias
     *
     * @return string
     */
    public function getAlias()
    {
        return 'sfcod_queue';
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    private function grabJobs(ContainerBuilder $container): array
    {
        $jobs = [];
        $path = realpath($container->getParameter('kernel.root_dir') . '/..');
        $finder = new Finder();
        $finder->in([$path]);
        $finder->exclude(['vendor', 'tests', 'Tests', 'Resources']);
        $finder->name(Glob::toRegex('*.php'));

        foreach ($finder->files() as $name => $file) {
            if (false === is_dir($file)) {
                $className = str_replace(realpath($container->getParameter('kernel.root_dir') . '/..'), '', realpath($file));
                $className = str_replace('/', '\\', $className);
                $className = substr($className, 0, -4);
                try {
                    if (class_exists($className)) {
                        $reflect = new \ReflectionClass($className);
                        if ($reflect->implementsInterface(\SfCod\QueueBundle\Base\JobInterface::class) && !$reflect->isAbstract()) {
                            $jobs[] = $reflect->getName();
                        }
                    }
                } catch (\RuntimeException $e) {
                }
            }
        }

        return $jobs;
    }

    /**
     * Create command
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function createCommands(array $config, ContainerBuilder $container)
    {
        $work = new Definition(WorkCommand::class);
        $work->setArguments([
            new Reference(LoggerInterface::class),
        ]);
        $work->addTag('console.command');

        $retry = new Definition(RetryCommand::class);
        $retry->addTag('console.command');

        $runJob = new Definition(RunJobCommand::class);
        $runJob->setArguments([
            new Reference(LoggerInterface::class),
        ]);
        $runJob->addTag('console.command');

        $container->addDefinitions([
            WorkCommand::class => $work,
            RetryCommand::class => $retry,
            RunJobCommand::class => $runJob,
        ]);
    }

    /**
     * Create driver
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function createDriver(array $config, ContainerBuilder $container)
    {
        $mongo = new Definition(MongoDriver::class);
        $mongo->setPublic(true);
        $mongo->setArguments([
            getenv('MONGODB_URL'),
            getenv('MONGODB_DB'),
        ]);

        $container->setDefinition(MongoDriver::class, $mongo);
    }

    /**
     * Create job queue
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function createJobQueue(array $config, ContainerBuilder $container)
    {
        $jobQueue = new Definition(JobQueue::class);
        $jobQueue->setPublic(true);
        $jobQueue->setArguments([
            new Reference(ContainerInterface::class),
            new Reference(MongoDriver::class),
            $config['connections'],
        ]);

        $container->setDefinition(JobQueue::class, $jobQueue);
    }

    /**
     * Create job process
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function createJobProcess(array $config, ContainerBuilder $container)
    {
        $jobProcess = new Definition(JobProcess::class);
        $jobProcess->setPublic(true);
        $jobProcess->setArguments([
            'console',
            sprintf('%s/bin', $container->getParameter('kernel.project_dir')),
        ]);

        $container->setDefinition(JobProcess::class, $jobProcess);
    }
}
