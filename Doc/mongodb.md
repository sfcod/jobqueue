#### Requirements:
```json
"mongodb/mongodb": "^1.1"
```

#### Configuration example:
```yaml
sfcod_queue:
    drivers:
        mongo: 'SfCod\QueueBundle\Connector\MongoConnector'
    connections:
        default: { driver: 'mongo', collection: 'queue_jobs', queue: 'default', expire: 360, limit: 2 }

services:
    SfCod\QueueBundle\Service\MongoDriver:
        calls:
            - [setCredentials, ['%env(MONGODB_URL)%']]
            - [setDbname, ['%env(MONGODB_NAME)%']]
    SfCod\QueueBundle\Connector\MongoConnector:
        arguments:
            - '@SfCod\QueueBundle\Base\JobResolverInterface'
            - '@SfCod\QueueBundle\Service\MongoDriver'
    SfCod\QueueBundle\Failer\FailedJobProviderInterface:
        class: SfCod\QueueBundle\Failer\MongoFailedJobProvider
        arguments:
            - '@SfCod\QueueBundle\Service\MongoDriver'
            - 'queue_jobs_failed'
```