# Внедрение зависимостей через сеттер-методы

## Обзор

Внедрение зависимостей в PHP класс может происходить так же через сеттер-методы. Для этого при настройке
определения контейнера используются методы [хелпер функции](../../documentation/php-definition/di-autowire.md) `diAutowire::setup()` и  `diAutowire::setupImmutable()`.

## diAutowire::setup()

Внедрение зависимости через сеттер-метод PHP класса [без учёта возвращаемого значения методом](../../documentation/php-definition/di-autowire.md#setup) (_mutable setter_).

Конфигурирование:
```php
// config/services.php
use function Kaspi\DiContainer\diAutowire;

return static function(): \Generator {

    yield 'priority_queue.get_data' => diAutowire(\SplPriorityQueue::class)
        ->setup('setExtractFlags', [\SplPriorityQueue::EXTR_DATA]);

};
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->load(...\glob(__DIR__.'/config/*.php'))
    ->build()
;

$priorityQueue = $container->get('priority_queue.get_data');
```

## diAutowire::setupImmutable()

Внедрение зависимости через [сеттер-метод возвращающий экземпляр PHP класса](../../documentation/php-definition/di-autowire.md#setupimmutable) (_immutable setter_).

Конфигурирование:

::: code-group

```php [config/services/services.php]
use function Kaspi\DiContainer\{diAutowire, diGet, diParameter};

return static function(): \Generator {
    yield diAutowire(App\Servces\FileLogger::class)
        ->bindArguments(fileName: diParameter('app.logger_file'));

    yield diAutowire(App\SomeClass::class)
        // Будет возвращён объект из метода `withLogger`
        ->setupImmutable('withLogger', [diGet(App\Servces\FileLogger::class)]);
};
```

```php [config/parameters/params.php]
return [
    'app.logger_file' => '/var/logs/application.log',
];
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;

$container = (new DiContainerBuilder())
    ->loadParameters(...\glob(__DIR__.'/config/parameters/*.php'))
    ->load(...\glob(__DIR__.'/config/services/*.php'))
    ->build()
;

// ...

$logger = $container->get(App\SomeClass::class)->getLogger();

\var_dump($logger instanceof Psr\Log\LoggerInterface::class);
// true
```

Классы для конфигурирования:

::: code-group

```php [App\SomeClass.php]
namespace App;

use Psr\Log\LoggerInterface;

class SomeClass {
    private LoggerInterface $logger;

    // other methods and properties.

    public function withLogger(LoggerInterface $logger): static
    {
        $new = clone $this;
        $new->logger = $logger;
    
        return $new;
    }
    
    public function getLogger(): ?LoggerInterface {
        return $this->logger ?? null;
    }
}
```

```php [App/Services/FileLogger.php]
namespace App\Services;

use Psr\Log\LoggerInterface;

class FileLogger implements LoggerInterface {

    public function __construct(private string $fileName) {}
    // implement methods from LoggerInterface
}
```

::: 
