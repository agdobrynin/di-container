# Service

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Service` указывает PHP интерфейсу его реализацию.

Конфигурация атрибута:
- `\Attribute::TARGET_CLASS`

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\Service::__construct(
    string $id,
    ?bool $isSingleton = null
)
```

Параметры:
- `$id` – идентификатор контейнера [^ContainerId] реализующий интерфейс.
- `$isSingleton` – возвращать один и тот же объект (паттерн singleton). Если значение `null`, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).

Параметр `$id` указывает реализацию интерфейса PHP классом, который доступен в контейнере через метод `get()`.

Объявление реализации интерфейса через атрибут `\Kaspi\DiContainer\Attributes\Service` является глобальным – если для конкретного параметра не объявлено как необходимо внедрять зависимость, то контейнер внедрит зависимость на основании конфигурации интерфейса, получив значение объекта из контейнера через метод `get()` со значением идентификатора указанного в параметре `$id`.

Например, указание строкового идентификатора `'services.qux'` в параметре `$id` будет означать что контейнеру необходимо внедрять объект полученный через вызов метода `$container->get('services.qux')`. 

## Пример конфигурирования интерфейса { #example-interface-service }

::: code-group


```php [QuxInterface.php]
<?php
declare(strict_types=1);

// file: /app/src/Interfaces/QuxInterface.php // [!code highlight]

namespace App\Interfaces;

use Kaspi\DiContainer\Attributes\Service;//[!code ++]
use App\Services\Bar;

#[Service(Bar::class)]//[!code ++]
interface QuxInterface
{
    // …
} 
```

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php //[!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar};
use App\Interfaces\QuxInterface;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')//[!code highlight]
    ->build();

$foo = $container->get(Foo::class);

var_dump($foo->qux instanceof QuxInterface);
// bool(true)

var_dump($foo->qux instanceof Bar);
// bool(true)
```

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

final class Foo
{
    public function __construct(public readonly QuxInterface $qux) {} 
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php //[!code highlight]

namespace App\Services;

use App\Interfaces\QuxInterface;

final class Bar implements QuxInterface
{
    // …
}
```

:::

Объявление реализации интерфейса `\App\Interfaces\QuxInterface` PHP классом `\App\Services\Bar` является глобальным. При внедрении зависимости с типом `\App\Interfaces\QuxInterface` контейнер всегда будет внедрять реализацию указанную в параметре `$id` атрибута `\Kaspi\DiContainer\Attributes\Service`.

## Конфигурация интерфейсов сторонних производителей { #inject-vendor-interface }

При внедрении через интерфейс от сторонних производителей, например из других PHP пакетов, нужно объявлять реализацию интерфейса через [файлы конфигураций](../container-builder/methods/load-definitions-from-file.md) или через [коллекцию](../container-builder/methods/load-definitions-from-collection.md) при сборке контейнера.

```php
<?php
declare(strict_types=1);

use Psr\Log\LoggerInterface;
use App\Services\MyLogger;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    LoggerInterface::class => diAutowire(MyLogger::class);
};
```

Пример объявления внедрения по интерфейсу стороннего производителя доступен в разделе «[Рецепты](../../cookbook/resolve-vendor-interface.md)».

<!--@include: ../_include/term_notes.md-->
