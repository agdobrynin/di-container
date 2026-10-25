# diGet()

## Обзор { #overview }

Хелпер функция `diGet()` внедряет зависимость через идентификатор контейнера [^ContainerId], это эквивалентно вызову метода контейнера `get()`.

Для случаев когда параметр метода или вызываемого типа[^CallableType] указан через определяемые пользователем типы (PHP класс, встроенные PHP классы, перечисления) применять хелпер функцию `diGet()` нет необходимости, контейнер внедряет зависимость автоматически на основании конфигурации.

Использовать хелпер функцию `diGet()` для внедрения зависимости необходимо когда контейнер не может автоматически определить тип зависимости в конкретном параметре метода или вызываемого типа, в таком случае нужна конкретизация внедряемого типа. Например, параметр метода указан с типом `mixed` или представлен составным типом (пересечение класс-типов, объединение типов), в этом случае хелпер функция будет конкретизировать какое значение контейнера необходимо внедрить в параметр.


Сигнатура функции:

```php
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;

\Kaspi\DiContainer\diGet(
    string $containerIdentifier
): DiDefinitionNoArgumentsInterface
```

Параметры:
- `$containerIdentifier` – идентификатор контейнера [^ContainerId].

У хелпер функции нет дополнительных методов.

## Пример внедрение в параметр с типом mixed { #example }

Необходимо внедрить PHP класс `\App\Services\Bar` через конструктор в параметр `$entry` класса `\App\Services\Foo`.

::: code-group

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php // [!code highlight]

use App\Service\{Foo, Bar};

use function Kaspi\DiContainer\{diAutowire, diGet};


return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            entry: diGet(Bar::class)// [!code ++]
        );
};
```

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')
    ->build();

$foo = $container->get(Foo::class);// [!code highlight]

var_dump($foo->entry instanceof Bar);
// (bool) true
```

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

final class Foo
{
    public function __construct(public readonly mixed $entry) {}
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php //[!code highlight]

namespace App\Services;

final class Bar
{
    // …
}
```

:::

## Рецепты { #cookbook }

В разделе «Рецепты» представлены примеры конкретизации объявления внедрения параметров:
- [Внедрение зависимости по интерфейсу](../../cookbook/inject-interface.md#helper-di-get).
- [Внедрение зависимостей для параметров объединенного типа](../../cookbook/inject-union-type-params.md#php-definition).

<!--@include: ../_include/term_notes.md-->
