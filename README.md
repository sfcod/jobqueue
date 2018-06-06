Job Queue Bundle for Symfony
======================================================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sfcod/jobqueue/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sfcod/jobqueue/?branch=master)[![Code Climate](https://codeclimate.com/github/sfcod/jobqueue/badges/gpa.svg)](https://codeclimate.com/github/sfcod/jobqueue)

Provides async queues implementation for Symfony (using mongodb as main storage).

#### Config:
```yaml
sfcod_queue:
    connections:
        default: { driver: 'mongo-thread', collection: 'queue_jobs', queue: 'default', expire: 60, limit: 2 }
    namespaces:
        - 'App\Job'        
```
namespaces - is not requered. You can set here all namespace where your job classes are, otherwise they will be fetched as a public services from symfony container.
connection - is not requered. If it is not set, bundle will use this service SfCod\QueueBundle\Base\MongoDriverInterface::class as default.

#### Adding jobs to queue:

Create your own handler which implements SfCod\QueueBundle\Base\JobInterface 

```php
$jobQueue->push(your_job_handler_service, $data);
```

$data - additional data for your job

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

##### Main services:
```yaml
SfCod\QueueBundle\Service\JobQueue:
    public: true
    arguments:
        - '@SfCod\QueueBundle\Service\QueueManager'
```
SfCod\QueueBundle\Service\JobQueue: main job queue service.

```yaml
SfCod\QueueBundle\Service\QueueManager:
    calls: 
        - [addConnector, ['mongo-thread', '@SfCod\QueueBundle\Connector\ConnectorInterface']]
```
SfCod\QueueBundle\Service\QueueManager: queue manager which holds connectors and connections.

```yaml
SfCod\QueueBundle\Worker\Worker:
    arguments:
        - '@SfCod\QueueBundle\Service\QueueManager'
        - '@SfCod\QueueBundle\Service\JobProcess'
        - '@SfCod\QueueBundle\Failer\FailedJobProviderInterface'
        - '@SfCod\QueueBundle\Handler\ExceptionHandlerInterface'
        - '@Symfony\Component\EventDispatcher\EventDispatcherInterface'
```
SfCod\QueueBundle\Worker\Worker: main worker service.

```yaml
SfCod\QueueBundle\Service\JobProcess:
    arguments:
        - 'console'
        - '%kernel.project_dir%/bin'
        - 'php'
        - ''
```
SfCod\QueueBundle\Service\JobProcess: default config for jobs command processor in async queues, where: 
- 'console' - name of console command 
- '%kernel.project_dir%/bin' - path for console command
- 'php' - binary script
- '' - binary script arguments

##### Connector
```yaml
SfCod\QueueBundle\Connector\ConnectorInterface:
    class: SfCod\QueueBundle\Connector\MongoConnector
    arguments:
        - '@SfCod\QueueBundle\Base\JobResolverInterface'
        - '@SfCod\QueueBundle\Base\MongoDriverInterface'
```
SfCod\QueueBundle\Connector\ConnectorInterface: connector for queues' database.

##### Job resolver
```yaml
SfCod\QueueBundle\Base\JobResolverInterface:
    class: SfCod\QueueBundle\Service\JobResolver
    arguments:
        - '@Symfony\Component\DependencyInjection\ContainerInterface'
```
SfCod\QueueBundle\Base\JobResolverInterface: resolver for jobs, it builds job using job's display name, for default jobs fetches from container as a public services.

##### Failed jobs provider
```yaml
SfCod\QueueBundle\Failer\FailedJobProviderInterface:
    class: SfCod\QueueBundle\Failer\MongoFailedJobProvider
    arguments:
        - '@SfCod\QueueBundle\Base\MongoDriverInterface'
        - 'queue_jobs_failed'
```
SfCod\QueueBundle\Failer\FailedJobProviderInterface: failer service for failed jobs processing, where:
- SfCod\QueueBundle\Base\MongoDriverInterface - mongo driver
- 'queue_jobs_failed' - name of mongo collection

##### Exception handler
```yaml
SfCod\QueueBundle\Handler\ExceptionHandlerInterface:
    class: SfCod\QueueBundle\Handler\ExceptionHandler
    arguments:
        - '@Psr\Log\LoggerInterface'
```
SfCod\QueueBundle\Handler\ExceptionHandlerInterface: main exception handler, used for logging issues

##### Mongo driver config:

```yaml
SfCod\QueueBundle\Base\MongoDriverInterface:
    class: SfCod\QueueBundle\Service\MongoDriver
    calls:
        - [setCredentials, ['%env(MONGODB_URL)%']]
        - [setDbname, ['%env(MONGODB_NAME)%']]
```
SfCod\QueueBundle\Base\MongoDriverInterface: default config for mongo driver connection

##### New connector:

If you want to change default connector, you can override SfCod\QueueBundle\Connector\ConnectorInterface or add method call:
```yaml
SfCod\QueueBundle\Service\QueueManager:
    calls: 
        - [addConnector, ['your-connector', '@your.service']]
```
where 'your.service' must implement SfCod\QueueBundle\Connector\ConnectorInterface and then all your connections with driver 'your-connector' will be processed using new connector, for example:
```yaml
sfcod_queue:
    connections:
        default: { driver: 'your-connector', collection: 'queue_jobs' queue: 'default', expire: 60, limit: 2 }
```
