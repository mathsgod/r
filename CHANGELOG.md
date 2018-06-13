### 2.4.1
check session is started

---

### 2.3.1
RSList allow create if null value are input

---

### 2.3.0
exception not catch in Page now, catch by app::run

---

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