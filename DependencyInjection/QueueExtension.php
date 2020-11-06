<?php

namespace SfCod\QueueBundle\DependencyInjection;

use Psr\Log\LoggerInterface;
use SfCod\QueueBundle\Base\JobResolverInterface;
use SfCod\QueueBundle\Command\RetryCommand;
use SfCod\QueueBundle\Command\RunJobCommand;
use SfCod\QueueBundle\Command\WorkCommand;
use SfCod\QueueBundle\Failer\FailedJobProviderInterface;
use SfCod\QueueBundle\Handler\ExceptionHandler;
use SfCod\QueueBundle\Handler\ExceptionHandlerInterface;
use SfCod\QueueBundle\Service\JobProcess;
use SfCod\QueueBundle\Service\JobQueue;
use SfCod\QueueBundle\Service\JobResolver;
use SfCod\QueueBundle\Service\QueueManager;
use SfCod\QueueBundle\Worker\Worker;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
     * @param array $config
     * @param ContainerBuilder $container
     *
     * @throws \ReflectionException
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $configuration = new QueueConfiguration();

        $config = $this->processConfiguration($configuration, $config);

        $jobs = $this->grabJobs($config, $container);
        foreach ($jobs as $job) {
            $definition = new Definition($job);
            $definition
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setPublic(true)
                ->addTag('sfcod.jobqueue.job');
            $container->setDefinition($job, $definition);
        }

        $this->createJobQueue($config, $container);
        $this->createWorker($config, $container);
        $this->createJobProcess($config, $container);
        $this->createCommands($config, $container);
        $this->createManager($config, $container);
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
     * @deprecated will be removed some day, use services with tag "sfcod.jobqueue.job"
     *
     * @param array $config
     *
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function grabJobs(array $config, ContainerBuilder $container): array
    {
        $jobs = [];
        foreach ($config['namespaces'] as $key => $namespace) {
            $alias = $container->getParameter('kernel.root_dir') . '/../' . str_replace('\\', DIRECTORY_SEPARATOR, trim($namespace, '\\'));

            foreach (glob(sprintf('%s/**.php', $alias)) as $file) {
                $className = sprintf('%s\%s', $namespace, basename($file, '.php'));
                if (method_exists($className, 'fire')) {
                    $jobs[] = $className;
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
            new Reference(Worker::class),
        ]);
        $work->addTag('console.command');

        $retry = new Definition(RetryCommand::class);
        $retry->setArguments([
            new Reference(JobQueue::class),
            new Reference(FailedJobProviderInterface::class),
        ]);
        $retry->addTag('console.command');

        $runJob = new Definition(RunJobCommand::class);
        $runJob->setArguments([
            new Reference(Worker::class),
        ]);
        $runJob->addTag('console.command');

        $container->addDefinitions([
            WorkCommand::class => $work,
            RetryCommand::class => $retry,
            RunJobCommand::class => $runJob,
        ]);
    }

    /**
     * Create queue manager
     *
     * @param array $config
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    private function createManager(array $config, ContainerBuilder $container)
    {
        $resolver = new Definition(JobResolverInterface::class);
        $resolver->setClass(JobResolver::class);

        $manager = new Definition(QueueManager::class);

        foreach ($config['drivers'] as $name => $service) {
            $manager->addMethodCall('addConnector', [
                $name,
                new Reference(ltrim($service, '@')),
            ]);
        }

        foreach ($config['connections'] as $name => $params) {
            $manager->addMethodCall('addConnection', [
                $params,
                $name,
            ]);
        }

        $container->addDefinitions([
            JobResolverInterface::class => $resolver,
            QueueManager::class => $manager,
        ]);
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
            new Reference(QueueManager::class),
        ]);

        $container->setDefinition(JobQueue::class, $jobQueue);
    }

    /**
     * Create worker
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function createWorker(array $config, ContainerBuilder $container)
    {
        $worker = new Definition(Worker::class);
        $worker
            ->setArguments([
                new Reference(QueueManager::class),
                new Reference(JobProcess::class),
                new Reference(FailedJobProviderInterface::class),
                new Reference(ExceptionHandlerInterface::class),
                new Reference(EventDispatcherInterface::class),
            ]);

        $exceptionHandler = new Definition(ExceptionHandlerInterface::class);
        $exceptionHandler
            ->setClass(ExceptionHandler::class)
            ->setArguments([
                new Reference(LoggerInterface::class),
            ]);

        $container->addDefinitions([
            Worker::class => $worker,
            ExceptionHandlerInterface::class => $exceptionHandler,
        ]);
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
        $jobProcess->setArguments([
            'console',
            sprintf('%s/bin', $container->getParameter('kernel.project_dir')),
            $container->getParameter('kernel.environment'),
        ]);

        $container->setDefinition(JobProcess::class, $jobProcess);
    }
}
