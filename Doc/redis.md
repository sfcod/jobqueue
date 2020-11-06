#### Requirements:
```json
"predis/predis": "^1.1"
```

#### Configuration example:
```yaml
sfcod_queue:
    drivers:
        redis: 'SfCod\QueueBundle\Connector\RedisConnector'
    connections:
        default: { driver: 'redis', collection: 'queue_jobs', queue: 'default', expire: 360, limit: 2 }

services:
    SfCod\QueueBundle\Service\RedisDriver:
        arguments:
            - '%env(REDIS_URL)%'
    SfCod\QueueBundle\Connector\RedisConnector:
        arguments:
            - '@SfCod\QueueBundle\Base\JobResolverInterface'
            - '@SfCod\QueueBundle\Service\RedisDriver'
    SfCod\QueueBundle\Failer\FailedJobProviderInterface:
        class: SfCod\QueueBundle\Failer\RedisFailedJobProvider
        arguments:
            - '@SfCod\QueueBundle\Service\RedisDriver'
            - 'queue_jobs_failed'
```