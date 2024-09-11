#### Requirements:
```json
"doctrine/dbal": "^4.1"
```

#### Configuration example:
```yaml
sfcod_queue:
    drivers:
      doctrine: 'SfCod\QueueBundle\Connector\DoctrineConnector'
    connections:
        default: { driver: 'doctrine', collection: 'queue_jobs', queue: 'default', expire: 360, limit: 2 }

services:
    SfCod\QueueBundle\Connector\DoctrineConnector:
        arguments:
            - '@SfCod\QueueBundle\Base\JobResolverInterface'
            - '@Doctrine\DBAL\Connection'
    SfCod\QueueBundle\Failer\FailedJobProviderInterface:
        class: SfCod\QueueBundle\Failer\DoctrineFailedJobProvider
        arguments:
            - '@Doctrine\DBAL\Connection'
            - 'queue_jobs_failed'
```
