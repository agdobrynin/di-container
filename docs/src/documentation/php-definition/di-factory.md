# diFactory()

## Обзор { #overview }

Хелпер функция `diFactory()` внедряет зависимость контейнера через паттерн «фабрика» [^FactoryPattern].

Сигнатура функции:

```php
use \Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;

\Kaspi\DiContainer\diFactory(
    string|array $definition,
    ?bool $isSingleton = null
): DiDefinitionArgumentsInterface
```

Параметры:
- `$definition` – представление php класса и метода фабрики.
- `$isSingleton` – возвращать один и тот же результат (паттерн singleton). Если значение null, то значение будет выбрано на основе [настройки контейнера](../container-config/index.md#is-singleton-service-default).

Функция `diFactory()` возвращает объект предоставляющий методы:
- `bindArguments()` – передать аргументы для метода фабрики.

> [!NOTE]
> Изучите отдельный раздел документации посвященный использованию «[фабричных методов](../07-factory.md)» для внедрения зависимостей.

## bindArguments()

Передача аргументов для метода фабрики.

<!--@include: ./_include/bind_arguments.md-->

## Идентификатор контейнера { #container-id }

Чтобы обеспечить доступность «фабрики» через метод контейнера `get()` и `has()` необходимо указать в [файле конфигураций](../container-builder/configuration_files.md) соответствующий идентификатор контейнера[^ContainerId].

::: code-group

```php [container.php]
<?php
declare(strict_types=1);

// file: /app/src/container.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';// [!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/factories.php')// [!code highlight]
    ->build();

$result = $container->get('services.foo_factory');// [!code highlight]

var_dump($result);
// string(26) "Lorem ipsum dolor sit amet"
```

```php [factories.php]
<?php
declare(strict_types=1);

// file: /app/config/factories.php// [!code highlight]

use App\Services\Foo;

use function Kaspi\DiContainer\diFactory;

return static function(): Generator {
    yield 'services.foo_factory' => diFactory([Foo::class, 'createValue'])//[!code ++]
        ->bindArguments(//[!code ++]
           prefixValue: 'Lorem ipsum'//[!code ++]
        );//[!code ++]
};
```

```php [Foo.php]
<?php
declare(strict_types=1);

//file: /app/src/Services/Foo.php// [!code highlight]

namespace App\Services;

final class Foo
{
    public function __construct(private readonly Bar $bar) {}
    
    public function createValue(string $prefixValue): string
    {
        return $prefixValue.' '.$this->bar->doSomething();
    }
}

```

```php [Bar.php]
<?php
declare(strict_types=1);

//file: /app/src/Services/Bar.php

namespace App\Services;

final class Bar
{
    // …

    public function doSomething(): string
    {
        return 'dolor sit amet';
    }
}
```

:::

<!--@include: ../_include/term_notes.md-->
