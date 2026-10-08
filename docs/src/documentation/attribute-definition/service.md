# Service

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Service` указывает PHP интерфейсу его реализацию.

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\Service::__construct(
    string $id,
    ?bool $isSingleton = null
)
```

Параметры:
- `$id` - FQCN [^FQCN] PHP класса реализующего интерфейс или идентификатор контейнера.
- `$isSingleton` – возвращать один и тот же объект (паттерн singleton). Если значение `null`, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).

----

⚠️⚠️⚠️⚠️

```php
// src/Loggers/CustomLoggerInterface.php
namespace App\Loggers;

use Kaspi\DiContainer\Attributes\Service;

#[Service(CustomLogger::class)] // класс реализующий данный интерфейс.
interface CustomLoggerInterface {
    public function loggerFile(): string;
}
```

```php
// src/Loggers/CustomLogger.php
namespace App\Loggers;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter as DiParameter;

#[Autowire(
    arguments: [
        'file' => new DiParameter('logger.file_name')    
    ]
)]
class CustomLogger implements CustomLoggerInterface {

    public function __construct(
        protected string $file,
    ) {}
    
    public function loggerFile(): string {
        return $this->file;
    }
}
```
```php
// src/Loggers/MyLogger.php
namespace App\Loggers;

class MyLogger {

    public function __construct(
        // Контейнер найдёт интерфейс
        // и проверит у него php-атрибут Service.
        public CustomLoggerInterface $customLogger
    ) {}
}
```

```php
// config/parameters.php
return [
    'logger.file_name' => '/var/log/app.log'
];
```

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->loadParameters(__DIR__.'/config/parameters.php')
    ->import(namespace: 'App\\', src: __DIR__.'/src/')
    ->build()
;

// Получение данных из контейнера с автоматическим связыванием зависимостей
$myClass = $container->get(App\Loggers\MyLogger::class);

print $myClass->customLogger->loggerFile(); // /var/log/app.log
```

Так же атрибут **Service** можно использовать со ссылкой на другой идентификатор контейнера.

```php
// src/Loggers/CustomLoggerInterface.php
namespace App\Loggers;

use Kaspi\DiContainer\Attributes\Service;

#[Service('services.app_logger')]
interface CustomLoggerInterface {
    public function loggerFile(): string;
}
```
```php
// src/Loggers/CustomLogger.php
namespace App\Loggers;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\DiDefinition\DiDefinitionParameter as DiParameter;

#[Autowire(
    id: 'services.app_logger',
    arguments: [
    'file' => new DiParameter('logger.file_name')    
    ]
)]
class CustomLogger implements CustomLoggerInterface {

    public function __construct(
        protected string $file,
    ) {}
    
    public function loggerFile(): string {
        return $this->file;
    }
}
```

<!--@include: ../_include/term_notes.md-->
