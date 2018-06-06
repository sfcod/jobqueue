<?php

namespace SfCod\QueueBundle\Worker;

use Exception;
use SfCod\QueueBundle\Event\JobExceptionOccurredEvent;
use SfCod\QueueBundle\Event\JobFailedEvent;
use SfCod\QueueBundle\Event\JobProcessedEvent;
use SfCod\QueueBundle\Event\JobProcessingEvent;
use SfCod\QueueBundle\Event\WorkerStoppingEvent;
use SfCod\QueueBundle\Exception\FatalThrowableException;
use SfCod\QueueBundle\Failer\FailedJobProviderInterface;
use SfCod\QueueBundle\Handler\ExceptionHandlerInterface;
use SfCod\QueueBundle\Job\JobContractInterface;
use SfCod\QueueBundle\Exception\MaxAttemptsExceededException;
use SfCod\QueueBundle\Queue\QueueInterface;
use SfCod\QueueBundle\Service\JobProcess;
use SfCod\QueueBundle\Service\QueueManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Thread worker for job queues
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class Worker
{
    /**
     * Events
     */
    const EVENT_RAISE_BEFORE_JOB = 'job_queue_worker.raise_before_job';
    const EVENT_RAISE_AFTER_JOB = 'job_queue_worker.raise_after_job';
    const EVENT_RAISE_EXCEPTION_OCCURED_JOB = 'job_queue_worker.raise_exception_occurred_job';
    const EVENT_RAISE_FAILED_JOB = 'job_queue_worker.raise_failed_job';
    const EVENT_STOP = 'job_queue_worker.stop';

    /**
     * QueueManager instance
     *
     * @var QueueManager
     */
    private $queueManager;

    /**
     * Logger instance
     *
     * @var ExceptionHandler
     */
    private $exceptions;

    /**
     * Failer instance
     *
     * @var FailedJobProviderInterface
     */
    private $failer;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var JobProcess
     */
    private $jobProcess;

    /**
     * Worker constructor.
     *
     * @param QueueInterface $queue
     * @param JobProcess $process
     * @param FailedJobProviderInterface $failer
     * @param ExceptionHandlerInterface $exceptions
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(QueueManager $queueManager,
                                JobProcess $process,
                                FailedJobProviderInterface $failer,
                                ExceptionHandlerInterface $exceptions,
                                EventDispatcherInterface $dispatcher)
    {
        $this->queueManager = $queueManager;
        $this->process = $process;
        $this->failer = $failer;
        $this->exceptions = $exceptions;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Listen to the given queue in a loop.
     *
     * @param string $connectionName
     * @param string $queue
     * @param Options $options
     */
    public function daemon(string $connectionName, string $queue, Options $options)
    {
        while (true) {
            if (false === $this->runNextJob($connectionName, $queue, $options)) {
                $this->sleep($options->sleep);
            }

            if ($this->memoryExceeded($options->memory)) {
                $this->stop();
            }
        }
    }

    /**
     * Process the next job on the queue.
     *
     * @param string $connectionName
     * @param string $queue
     * @param Options $options
     *
     * @return bool
     */
    public function runNextJob(string $connectionName, string $queue, Options $options)
    {
        $connection = $this->queueManager->connection($connectionName);
        $job = $this->getNextJob($connection, $queue);

        // If we're able to pull a job off of the stack, we will process it and then return
        // from this method. If there is no job on the queue, we will "sleep" the worker
        // for the specified number of seconds, then keep processing jobs after sleep.
        if ($job instanceof JobContractInterface && $connection->canRunJob($job)) {
            $connection->markJobAsReserved($job);
            $this->runInBackground($job, $connectionName);

            return true;
        }

        return false;
    }

    /**
     * Process the next job on the queue.
     *
     * @param string $connectionName
     * @param $id
     * @param Options $options
     */
    public function runJobById(string $connectionName, $id, Options $options)
    {
        try {
            $connection = $this->queueManager->connection($connectionName);
            $job = $connection->getJobById($id);

            // If we're able to pull a job off of the stack, we will process it and then return
            // from this method. If there is no job on the queue, we will "sleep" the worker
            // for the specified number of seconds, then keep processing jobs after sleep.
            if ($job instanceof JobContractInterface) {
                if (false === $job->reserved()) {
                    $connection->markJobAsReserved($job);
                }

                $this->process($connectionName, $job, $options);

                return;
            }
        } catch (Exception $e) {
            $this->exceptions->report($e);
        } catch (Throwable $e) {
            $this->exceptions->report(new FatalThrowableException($e));
        }

        $this->sleep($options->sleep);
    }

    /**
     * Make a Process for the Artisan command for the job id.
     *
     * @param JobContractInterface $job
     * @param string $connectionName
     */
    public function runInBackground(JobContractInterface $job, string $connectionName)
    {
        $process = $this->process->getProcess($job, $connectionName);

        $process->run();
    }

    /** Process the given job from the queue.
     *
     * @param string $connectionName
     * @param JobContractInterface $job
     * @param Options $options
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function process(string $connectionName, JobContractInterface $job, Options $options)
    {
        try {
            // First we will raise the before job event and determine if the job has already ran
            // over the its maximum attempt limit, which could primarily happen if the job is
            // continually timing out and not actually throwing any exceptions from itself.
            $this->raiseBeforeJobEvent($connectionName, $job);

            $this->markJobAsFailedIfAlreadyExceedsMaxAttempts(
                $connectionName, $job, (int)$options->maxTries
            );

            // Here we will fire off the job and let it process. We will catch any exceptions so
            // they can be reported to the developers logs, etc. Once the job is finished the
            // proper events will be fired to let any listeners know this job has finished.
            $job->fire();

            $this->raiseAfterJobEvent($connectionName, $job);
        } catch (Exception $e) {
            $this->handleJobException($connectionName, $job, $options, $e);
        } catch (Throwable $e) {
            $this->handleJobException(
                $connectionName, $job, $options, new FatalThrowableException($e)
            );
        }
    }

    /**
     * Sleep the script for a given number of seconds.
     *
     * @param int $seconds
     *
     * @return void
     */
    public function sleep(int $seconds)
    {
        sleep($seconds);
    }

    /**
     * Determine if the memory limit has been exceeded.
     *
     * @param int $memoryLimit
     *
     * @return bool
     */
    public function memoryExceeded(int $memoryLimit)
    {
        return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
    }

    /**
     * Stop listening and bail out of the script.
     *
     * @param int $status
     */
    public function stop(int $status = 0)
    {
        $this->dispatcher->dispatch(self::EVENT_STOP, new WorkerStoppingEvent());

        exit(0);
    }

    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
     *
     * This will likely be because the job previously exceeded a timeout.
     *
     * @param string $connectionName
     * @param JobContractInterface $job
     * @param int $maxTries
     *
     * @return void
     */
    protected function markJobAsFailedIfAlreadyExceedsMaxAttempts(string $connectionName, JobContractInterface $job, int $maxTries)
    {
        $maxTries = !is_null($job->maxTries()) ? $job->maxTries() : $maxTries;

        $timeoutAt = $job->timeoutAt();

        if ($timeoutAt && time() <= $timeoutAt) {
            return;
        }

        if (!$timeoutAt && (0 === $maxTries || $job->attempts() <= $maxTries)) {
            return;
        }

        $this->failJob($connectionName, $job, $e = new MaxAttemptsExceededException(
            'A queued job has been attempted too many times or run too long. The job may have previously timed out.'
        ));

        throw $e;
    }

    /**
     * Mark the given job as failed and raise the relevant event.
     *
     * @param string $connectionName
     * @param JobContractInterface $job
     * @param Exception $e
     */
    protected function failJob(string $connectionName, JobContractInterface $job, Exception $e)
    {
        if ($job->isDeleted()) {
            return;
        }

        try {
            // If the job has failed, we will delete it, call the "failed" method and then call
            // an event indicating the job has failed so it can be logged if needed. This is
            // to allow every developer to better keep monitor of their failed queue jobs.
            $job->delete();

            $job->failed($e);
        } finally {
            $this->failer->log($connectionName, $job->getQueue(), $job->getRawBody(), $e);
            $this->raiseFailedJobEvent($connectionName, $job, $e);
        }
    }

    /**
     * Handle an exception that occurred while the job was running.
     *
     * @param string $connectionName
     * @param JobContractInterface $job
     * @param Options $options
     * @param Exception $e
     *
     * @return void
     *
     * @throws Exception
     */
    protected function handleJobException(string $connectionName, JobContractInterface $job, Options $options, Exception $e)
    {
        try {
            // First, we will go ahead and mark the job as failed if it will exceed the maximum
            // attempts it is allowed to run the next time we process it. If so we will just
            // go ahead and mark it as failed now so we do not have to release this again.
            if (!$job->hasFailed()) {
                $this->markJobAsFailedIfWillExceedMaxAttempts(
                    $connectionName, $job, (int)$options->maxTries, $e
                );
            }

            $this->raiseExceptionOccurredJobEvent(
                $connectionName, $job, $e
            );
        } finally {
            // If we catch an exception, we will attempt to release the job back onto the queue
            // so it is not lost entirely. This'll let the job be retried at a later time by
            // another listener (or this same one). We will re-throw this exception after.
            if (!$job->isDeleted() && !$job->isReleased() && !$job->hasFailed()) {
                $job->release($options->delay);
            }
        }

        throw $e;
    }

    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
     *
     * @param string $connectionName
     * @param JobContractInterface $job
     * @param int $maxTries
     * @param Exception $e
     *
     * @return void
     */
    protected function markJobAsFailedIfWillExceedMaxAttempts(string $connectionName, JobContractInterface $job, int $maxTries, Exception $e)
    {
        $maxTries = !is_null($job->maxTries()) ? $job->maxTries() : $maxTries;

        if ($job->timeoutAt() && $job->timeoutAt() <= time()) {
            $this->failJob($connectionName, $job, $e);
        }

        if ($maxTries > 0 && $job->attempts() >= $maxTries) {
            $this->failJob($connectionName, $job, $e);
        }
    }

    /**
     * Get the next job from the queue connection.
     *
     * @param QueueInterface $connection
     * @param string $queue
     *
     * @return JobContractInterface|null
     */
    protected function getNextJob(QueueInterface $connection, string $queue): ?JobContractInterface
    {
        try {
            foreach (explode(',', $queue) as $queue) {
                if (!is_null($job = $connection->pop($queue))) {
                    return $job;
                }
            }
        } catch (Exception $e) {
            $this->exceptions->report($e);
        } catch (Throwable $e) {
            $this->exceptions->report($e = new FatalThrowableException($e));
        }

        return null;
    }

    /**
     * Raise the before queue job event.
     *
     * @param string $connectionName
     * @param JobContractInterface $job
     */
    protected function raiseBeforeJobEvent(string $connectionName, JobContractInterface $job)
    {
        $this->dispatcher->dispatch(self::EVENT_RAISE_AFTER_JOB, new JobProcessingEvent($connectionName, $job));
    }

    /**
     * Raise the after queue job event.
     *
     * @param string $connectionName
     * @param JobContractInterface $job
     */
    protected function raiseAfterJobEvent(string $connectionName, JobContractInterface $job)
    {
        $this->dispatcher->dispatch(self::EVENT_RAISE_AFTER_JOB, new JobProcessedEvent($connectionName, $job));
    }

    /**
     * Raise the exception occurred queue job event.
     *
     * @param string $connectionName
     * @param JobContractInterface $job
     * @param Exception $e
     */
    protected function raiseExceptionOccurredJobEvent(string $connectionName, JobContractInterface $job, Exception $e)
    {
        $this->dispatcher->dispatch(self::EVENT_RAISE_EXCEPTION_OCCURED_JOB, new JobExceptionOccurredEvent($connectionName, $job, $e));
    }

    /**
     * Raise the failed queue job event.
     *
     * @param string $connectionName
     * @param JobContractInterface $job
     * @param Exception $e
     */
    protected function raiseFailedJobEvent(string $connectionName, JobContractInterface $job, Exception $e)
    {
        $this->dispatcher->dispatch(self::EVENT_RAISE_FAILED_JOB, new JobFailedEvent($connectionName, $job, $e));
    }
}
