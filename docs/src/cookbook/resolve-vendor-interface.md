# Внедрение по интерфейсу сторонних производителей { #title }

## Обзор { #overview }

**Для глобального конфигурирования** внедрения по интерфейсу от сторонних производителей, например из других PHP пакетов, 
нужно указать контейнеру какой PHP класс будет реализацией этого интерфейса. Это можно сделать используя методы [DiContainerBuilder::load()](../container-builder/index.md#load-definitions-from-file) или [DiContainerBuilder::addDefinitions()](../container-builder/index.md#load-definitions-from-collection) при сборке контейнера.

Для внедрения зависимости **только в параметр метода** можно использовать [атрибуты конфигурирования](../documentation/attribute-definition/index.md) или использовать хелпер функции для передачи аргументов в [файлах конфигураций](../documentation/container-builder/configuration_files.md).

Рассмотрим ниже конфигурацию контейнера для `\Psr\Log\LoggerInterface` [PSR-3](https://www.php-fig.org/psr/psr-3/)
и PHP пакета [Monolog](https://packagist.org/packages/monolog/monolog).

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
                diParameter('logger.name')
            );

    // Конфигурация хендлера из "monolog/monolog"
    yield diAutowire(StreamHandler::class)
        ->bingArguments(
            diParameter('logger.file'),
            diParameter('logger.level'),
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

var_dump($foo->$logger instanceof LoggerInterface);
// bool (true)
var_dump($foo->$logger instanceof Logger);
// bool (true)


$bar = $container->get(Bar::class);

var_dump($bar->$logger instanceof LoggerInterface);
// bool (true)
var_dump($bar->$logger instanceof Logger);
// bool (true)
```


## Внедрение зависимости «по-месту» { #parameter-config }

Если необходимо указать реализацию интерфейса `\Psr\Log\LoggerInterface` как PHP класс `\Monolog\Logger` только для параметра конструктора `\App\Services\Qux::$logger`,
можно задействовать [фабрику `\App\Helpers\Configurator::doConfigureMonolog()`](#do-configure-monolog) для внедрения. 

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

Конфигурирование:

```php
// file: /app/config/services/logger.php
use Generator;
use Psr\Log\LoggerInterface;
use App\Helpers\Configurator;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

use function Kaspi\DiContainer\{diFactory, diParameter};

return static function (): Generator {
    yield 'factory.create_monolog' => diFactory(
            [Configurator::class, 'doConfigureMonolog'],
            isSingleton: true,
        )
            ->bindArguments(
                // имя логгера
                'logger_qux',
                // Конфигурация хендлера
                diAutowire(StreamHandler::class)
                    // аргументы для конструктора `StreamHandler`
                    ->bingArguments(
                        '/var/logs/logger_qux.log',
                        Level::Info,
                )
            );
};
```


### Конфигурирование классов в файлах конфигураций { #parameter-config-attributes }

### Конфигурирование классов через атрибуты { #parameter-config-configuration-files }

<!--@include: ../documentation/_include/term_notes.md-->
