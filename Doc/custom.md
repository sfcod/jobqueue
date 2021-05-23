#### Connector with Queue (for main jobs processing):
Create and register your own connector as a service:
```php
namespace YourApp\Service;

use SfCod\QueueBundle\Connector\ConnectorInterface;

class CustomConnector implements ConnectorInterface {...}
```
Create CustomQueue and use it in your CustomConnector:
```php
namespace YourApp\Queue;

use SfCod\QueueBundle\Queue\Queue;

class CustomQueue extends Queue {...}
```

#### Failer (for failed jobs processing):
```php
namespace YourApp\Service;

use SfCod\QueueBundle\Failer\FailedJobProviderInterface;

class CustomFailer implements FailedJobProviderInterface {...}
```

#### Final minimal configuration example:
```yaml
sfcod_queue:
    drivers:
        custom: 'YourApp\Service\CustomConnector'
    connections:
        default: { driver: 'custom', collection: 'queue_jobs', queue: 'default', expire: 360, limit: 2 }

services:
    SfCod\QueueBundle\Failer\FailedJobProviderInterface:
        class: YourApp\Service\CustomFailer
```