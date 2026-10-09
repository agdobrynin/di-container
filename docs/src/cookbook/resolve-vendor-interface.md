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
        private LoggerInterface $logger
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
        private LoggerInterface $logger
        // другие зависимости
    ) {}    
}
```

:::

## Глобальная конфигурация реализации интерфейса { #global-config }

Чтобы любой параметр с типом `\Psr\Log\LoggerInterface` внедрял настроенный PHP класс `\Monolog\Logger` сделаем конфигурационный файл.
В конфигурационном файле укажем реализацию интерфейса через класс фабрику.

```php
// file: /app/config/logger.php
use Generator;
use Psr\Log\LoggerInterface;
use App\Helpers\Configurator;

use function Kaspi\DiContainer\diFactory;

return static function (): Generator {
    yield LoggerInterface::class => diFactory([Configurator::class, 'monolog'])
        ->bindArguments(
            diParameter('logger.name'),
            diParameter('logger.to_file'),
            diParameter('logger.level'),
        );
};
```

## Внедрение зависимости «по-месту» Конфигурация реализации интерфейса { #parameter-config }

### Атрибут `Inject` { #inject }

### Хелпер функция `diGet()` { #di-get }

<!--@include: ../documentation/_include/term_notes.md-->
