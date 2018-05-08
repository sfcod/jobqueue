Job Queue Bundle for Symfony based on Illuminate Queue
======================================================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sfcod/jobqueue/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sfcod/jobqueue/?branch=master)[![Code Climate](https://codeclimate.com/github/sfcod/jobqueue/badges/gpa.svg)](https://codeclimate.com/github/sfcod/jobqueue)

Provides Illuminate queues implementation for Symfony (using mongodb as main storage).

#### Config:
```yaml
sfcod_queue:
    connections:
        default: { driver: 'mongo-thread', collection: 'queue_jobs', connection: SfCod\QueueBundle\Service\MongoDriverInterface::class, queue: 'default', expire: 60, limit: 2 }
    namespaces:
        - 'App\Job'        
```
namespaces - is not requere. You can set here all namespace where your job classes are, otherwise they will be fetched as a services from symfony container.
connection - is not requere. If it is not set, bundle will use this service SfCod\QueueBundle\Service\MongoDriverInterface::class as default.

```yaml
services:
    SfCod\QueueBundle\Service\MongoDriverInterface:
        class: SfCod\QueueBundle\Service\MongoDriver
        public: true
        calls:
            - [setCredentials, ['%env(MONGODB_URL)%']]
            - [setDbname, ['%env(MONGODB_NAME)%']]

    SfCod\QueueBundle\JobProcess:
        public: true
        arguments:
            - 'console'
            - '%kernel.project_dir%/bin'
            - 'php'
            - ''
```

SfCod\QueueBundle\Service\MongoDriverInterface: default config for mongo driver connection,
SfCod\QueueBundle\JobProcess: default config for jobs command processor in async queues, where: 
- 'console' - name of console command 
- '%kernel.project_dir%/bin' - path for console command
- 'php' - binary script
- '' - binary script arguments

#### Adding jobs to queue:

Create your own handler which implements SfCod\QueueBundle\Base\JobInterface
OR extends SfCod\QueueBundle\Base\JobAbstract
and run parent::fire($job, $data) to restart db connection before job process if needed 

```php
$jobQueue->push(<--YOUR JOB SERVICE NAME->>, $data);
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