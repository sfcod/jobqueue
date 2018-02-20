Job Queue Bundle for Symfony based on Illuminate Queue
======================================================

Provides Illuminate queues implementation for Symfony (using mongodb as main storage).

#### Config:
```yaml
sfcod_queue:
    connections:
        default: { driver: 'mongo-thread', collection: 'queue_jobs', connectionName: 'default', queue: 'default', expire: 60, limit: 2 }
```

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