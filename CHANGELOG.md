### 2.1.0

logger added

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('R');
$log->pushHandler(new StreamHandler(__DIR__ . '/log.txt', Logger::DEBUG));

$app=new R\App(__DIR__,$loader,$logger);
$app->run();
```