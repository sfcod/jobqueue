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
namespaces - is requere. Set here all namespace where your job classes are.
connection - is not requere. If it is not set, bundle will use this service SfCod\QueueBundle\Service\MongoDriverInterface::class as default.

#### Adding jobs to queue:

Create your own handler which implements SfCod\QueueBundle\Base\JobInterface
OR extends SfCod\QueueBundle\Base\JobAbstract
and run parent::fire($job, $data) to restart db connection before job process 

```php
$jobQueue->push(<--YOUR JOB QUEUE CLASS NAME->>, $data);
```

Note: $data - additional data to your handler

#### Start worker:

Run worker daemon with console command: 
```php
$ php bin/console job-queue:work
```

Available events:
_________________

In Worker::class:
```php
EVENT_RAISE_BEFORE_JOB = 'raiseBeforeJobEvent';
EVENT_RAISE_AFTER_JOB = 'raiseAfterJobEvent';
EVENT_RAISE_EXCEPTION_OCCURED_JOB = 'raiseExceptionOccurredJobEvent';
EVENT_RAISE_FAILED_JOB = 'raiseFailedJobEvent';
EVENT_STOP = 'stop';
```