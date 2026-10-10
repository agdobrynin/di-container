---
outline: [2, 4]
---
# Внедрение по интерфейсу сторонних производителей { #title }

## Обзор { #overview }

**Для глобального конфигурирования** внедрения по интерфейсу от сторонних производителей, например из других PHP пакетов, 
нужно указать контейнеру какой PHP класс будет реализацией этого интерфейса. Это можно сделать используя методы [DiContainerBuilder::load()](../container-builder/index.md#load-definitions-from-file) или [DiContainerBuilder::addDefinitions()](../container-builder/index.md#load-definitions-from-collection) при сборке контейнера.

Для внедрения зависимости **только в параметр метода** можно использовать [атрибуты конфигурирования](../documentation/attribute-definition/index.md) или использовать хелпер функции для передачи аргументов в [файлах конфигураций](../documentation/container-builder/configuration_files.md).

Рассмотрим ниже конфигурацию контейнера для [PSR-3: Logger Interface](https://www.php-fig.org/psr/psr-3/) и PHP пакета [Monolog](https://packagist.org/packages/monolog/monolog).

PHP класс:

::: code-group

```php [Foo.php]
//file: /app/Services/Foo.php
namespace App\Services;

use Psr\Log\LoggerInterface;

final class Foo
{
    public function __construct(
        public readonly LoggerInterface $logger
        // другие зависимости
    ) {}    
}
```

```php [Bar.php]
//file: /app/Services/Foo.php
namespace App\Services;

use Psr\Log\LoggerInterface;

final class Bar
{
    public function __construct(
        public readonly LoggerInterface $logger
        // другие зависимости
    ) {}    
}
```

:::

## Глобальная конфигурация реализации интерфейса { #global-config }

Для внедрения в параметр с типом `\Psr\Log\LoggerInterface` необходимо настроить PHP класс `\Monolog\Logger` в контейнере.
Один из вариантов настроить конфигурацию `\Monolog\Logger` через «класс-фабрику»[^FactoryPattern] в файле конфигураций.

<span id="do-configure-monolog"/>Фабричный метод `\App\Helpers\Configurator::doConfigureMonolog()` создает и настраивает объект `\Monolog\Logger`:

```php
//file: /app/Helpers/Configurator.php
namespace App\Helpers;

use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

final class Configurator
{
    // …
    
    public static function doConfigureMonolog(
        string $loggerName,
        StreamHandler $handler,
    ): LoggerInterface {
        $log = new Logger('name');
        $log->pushHandler($handler);
        
        return $log;
    }
}
```

Конфигурирование:

::: code-group

```php [services/logger.php]
// file: /app/config/services/logger.php
use Generator;
use Psr\Log\LoggerInterface;
use App\Helpers\Configurator;
use Monolog\Handler\StreamHandler;

use function Kaspi\DiContainer\{diFactory, diParameter};

return static function (): Generator {
    yield LoggerInterface::class => diFactory(
            [Configurator::class, 'doConfigureMonolog'],
            isSingleton: true,
        )
            ->bindArguments(
                loggerName: diParameter('logger.name')
            );

    // Конфигурация хендлера из "monolog/monolog"
    yield diAutowire(StreamHandler::class)
        ->bingArguments(
            stream: diParameter('logger.file'),
            level: diParameter('logger.level'),
        );
};
```

```php [parameters/logger.php]
// file: /app/config/parameters/logger.php
use Monolog\Level;

return [
    'logger.name' => 'my-application',
    'logger.file' => '/var/logs/app.log',
    'logger.level' => Level::Warning,
];
```

:::

> [!NOTE]
> В методе `bingArguments()` для [конструктора `\Monolog\Handler\StreamHandler::__construct()`](https://github.com/Seldaek/monolog/blob/main/src/Monolog/Handler/StreamHandler.php) используем именованные аргументы.

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar};
use Psr\Log\LoggerInterface;
use Monolog\Logger;

$container = (new DiContainerBuilder())
    ->loadParameters('/app/config/parameters/logger.php')
    ->load('/app/config/services/logger.php')
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->logger instanceof LoggerInterface);
// bool (true)
var_dump($foo->logger instanceof Logger);
// bool (true)


$bar = $container->get(Bar::class);

var_dump($bar->logger instanceof LoggerInterface);
// bool (true)
var_dump($bar->logger instanceof Logger);
// bool (true)
```


## Внедрение в параметр метода – «по-месту» { #parameter-config }

Если необходимо указать реализацию интерфейса `\Psr\Log\LoggerInterface` как PHP класс `\Monolog\Logger` только для параметра конструктора `\App\Services\Qux::$logger`,
можно конфигурировать PHP класс `\Monolog\Logger` отдельно, а для параметра `\App\Services\Qux::$logger` указать какой PHP класс будет реализацией интерфейса.


Конфигурирование `\Monolog\Logger`:

```php
// file: /app/config/services/monolog.php
use Generator;
use Psr\Log\LoggerInterface;
use App\Helpers\Configurator;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Level;

use function Kaspi\DiContainer\{diFactory, diParameter};

return static function (): Generator {
    yield diAutowire(Logger::class, isSingleton: true)
        ->bindArguments(name: 'logger_qux')
        ->setup('pushHandler', arguments: [
            // Конфигурация хендлера
            'handle' => diAutowire(StreamHandler::class)
                    // аргументы для конструктора `StreamHandler`
                    ->bingArguments(
                        stream: '/var/logs/logger_qux.log',
                        level: Level::Info,
                )
        ]);
};
```

> [!NOTE]
> В методе `bingArguments()` для [конструктора `\Monolog\Handler\StreamHandler::__construct()`](https://github.com/Seldaek/monolog/blob/main/src/Monolog/Handler/StreamHandler.php) используем именованные аргументы.

### Атрибут `Inject` { #parameter-attribute-inject }

Конфигурирование через [PHP атрибуты](../documentation/attribute-definition/index.md).

PHP класс:

```php
// file: /app/src/Services/Qux.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Inject;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final class Qux
{
    public function __construct(
        #[Inject(Logger::class)]
        public readonly LoggerInterface $logger
    ) {}
}
```

#### Контейнер зависимостей { #attribute-inject-by-container }

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Qux;
use Psr\Log\LoggerInterface;
use Monolog\Logger;

$container = (new DiContainerBuilder())
    ->load('/app/config/services/monolog.php')
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$qux = $container->get(Qux::class);

var_dump($qux->logger instanceof LoggerInterface);
// bool (true)
var_dump($qux->logger instanceof Logger);
// bool (true)
```

### Хелпер функция `diGet()` { #parameter-helper-di-get }

Конфигурирование без PHP атрибутов, в силе [php определений](../documentation/php-definition/index.md).

PHP класс:

```php
// file: /app/src/Services/Qux.php
namespace App\Services;

use Psr\Log\LoggerInterface;

final class Qux
{
    public function __construct(
        public readonly LoggerInterface $logger
    ) {}
}
```

Конфигурация класса `\App\Services\Qux`:

```php
// file: /app/config/services/qux_class.php
use Generator;
use App\Services\Qux;
use Monolog\Logger;

use function Kaspi\DiContainer\diGet;

return static function (): Generator {
    yield diAutowire(Qux::class)
        ->bingArguments(
            logger: diGet(Logger::class)
        );
};
```

#### Контейнер зависимостей { #helper-di-get-by-container }

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Qux;
use Psr\Log\LoggerInterface;
use Monolog\Logger;

$container = (new DiContainerBuilder())
    ->load(
        '/app/config/services/monolog.php',
        '/app/config/services/qux_class.php',
    )
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$qux = $container->get(Qux::class);

var_dump($qux->logger instanceof LoggerInterface);
// bool (true)
var_dump($qux->logger instanceof Logger);
// bool (true)
```

<!--@include: ../documentation/_include/term_notes.md-->
