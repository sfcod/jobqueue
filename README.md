Job Queue Bundle for Symfony
======================================================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sfcod/jobqueue/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sfcod/jobqueue/?branch=master)[![Code Climate](https://codeclimate.com/github/sfcod/jobqueue/badges/gpa.svg)](https://codeclimate.com/github/sfcod/jobqueue)

Provides async queues implementation for Symfony (using mongodb as main storage).

#### Supported drivers (storages):
- [MongoDB](Doc/mongodb.md)
- [Redis](Doc/redis.md)
- [Custom](Doc/custom.md)

#### Config:
Register the bundle config and all available "Jobs"
```yaml
sfcod_queue:
    drivers:
        redis: 'SfCod\QueueBundle\Connector\RedisConnector'
    connections:
        default: { driver: 'redis', collection: 'queue_jobs', queue: 'default', expire: 360, limit: 2 }  

services:
#    _instanceof:
#        SfCod\QueueBundle\Base\JobInterface:
#            tags: ['sfcod.jobqueue.job']
    App\Job\:
        resource: '../src/Job/*'
        tags: ['sfcod.jobqueue.job']
```

#### Adding jobs to the queue:

Create your own "job" which implements SfCod\QueueBundle\Base\JobInterface and run it: 

```php
public function someFunc(JobQueue $jobQueue) {
    $data = [...];
    $jobQueue->push(YourJob::class, $data);
}
```
where $data is a payload for your job

#### Commands:

Run worker daemon with console command: 
```php
$ php bin/console job-queue:work
$ php bin/console job-queue:retry --id=<Job ID>
$ php bin/console job-queue:run-job <Job ID>
```

Where: 
- work - command to run daemon in loop;
- retry - command to move all failed jobs back into queue, can be used with --id param to retry only single job
- run-job - command to run single job by id

#### Available events:
```php
'job_queue_worker.raise_before_job': SfCod\QueueBundle\Event\JobProcessingEvent;
'job_queue_worker.raise_after_job': SfCod\QueueBundle\Event\JobProcessedEvent;
'job_queue_worker.raise_exception_occurred_job': SfCod\QueueBundle\Event\JobExceptionOccurredEvent;
'job_queue_worker.raise_failed_job': SfCod\QueueBundle\Event\JobFailedEvent;
'job_queue_worker.stop': SfCod\QueueBundle\Event\WorkerStoppingEvent;
```

#### Configurable services list (with default parameters):

##### JobQueue:
```yaml
SfCod\QueueBundle\Service\JobQueue:
    public: true
    arguments:
        - '@SfCod\QueueBundle\Service\QueueManager'
```
SfCod\QueueBundle\Service\JobQueue: main job queue service

##### Worker
```yaml
SfCod\QueueBundle\Worker\Worker:
    arguments:
        - '@SfCod\QueueBundle\Service\QueueManager'
        - '@SfCod\QueueBundle\Service\JobProcess'
        - '@SfCod\QueueBundle\Failer\FailedJobProviderInterface'
        - '@SfCod\QueueBundle\Handler\ExceptionHandlerInterface'
        - '@Symfony\Component\EventDispatcher\EventDispatcherInterface'
```
SfCod\QueueBundle\Worker\Worker: async worker for "work" command

##### JobProcess
```yaml
SfCod\QueueBundle\Service\JobProcess:
    arguments:
        - 'console'
        - '%kernel.project_dir%/bin'
        - 'php'
        - ''
``` 
JobProcess: default config for jobs command processor in async queues, where:
- 'console' - name of console command 
- '%kernel.project_dir%/bin' - path for console command
- 'php' - binary script
- '' - binary script arguments

##### Connector
```yaml
SfCod\QueueBundle\Connector\ConnectorInterface:
    class: SfCod\QueueBundle\Connector\RedisConnector
    arguments:
        - '@SfCod\QueueBundle\Base\JobResolverInterface'
        - '@SfCod\QueueBundle\Base\RedisDriver'
```
SfCod\QueueBundle\Connector\ConnectorInterface: connector for queues' database

##### Failer
```yaml
SfCod\QueueBundle\Failer\FailedJobProviderInterface:
    class: SfCod\QueueBundle\Failer\RedisFailedJobProvider
    arguments:
        - '@SfCod\QueueBundle\Service\RedisDriver'
        - 'queue_jobs_failed'
```
SfCod\QueueBundle\Failer\FailedJobProviderInterface: storage for failed jobs

##### Job resolver
```yaml
SfCod\QueueBundle\Base\JobResolverInterface:
    class: SfCod\QueueBundle\Service\JobResolver
    arguments:
        - '@Symfony\Component\DependencyInjection\ContainerInterface'
```
SfCod\QueueBundle\Base\JobResolverInterface: resolver for jobs, it builds job using job's display name, for default jobs fetches from container as a public services.

##### Exception handler
```yaml
SfCod\QueueBundle\Handler\ExceptionHandlerInterface:
    class: SfCod\QueueBundle\Handler\ExceptionHandler
    arguments:
        - '@Psr\Log\LoggerInterface'
```
SfCod\QueueBundle\Handler\ExceptionHandlerInterface: main exception handler, used for logging issues

##### Testing:
You can run tests using prepared configuration xml file:
```php
php bin/phpunit --configuration ./vendor/sfcod/jobqueue/phpunit.xml.dist --bootstrap ./vendor/autoload.php
```
