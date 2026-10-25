# Inject

## Обзор { #overview }

Атрибут `\Kaspi\DiContainer\Attributes\Inject` внедряет зависимость через идентификатор контейнера [^ContainerId], это эквивалентно вызову метода контейнера `get()`.

Для случаев когда параметр метода или вызываемого типа[^CallableType] указан через определяемые пользователем типы (PHP класс, встроенные PHP классы, перечисления) применять атрибут `Inject` нет необходимости, контейнер внедряет зависимость автоматически на основании конфигурации.

Применение атрибута `Inject` необходимо когда контейнер не может автоматически определить тип зависимости в конкретном параметре метода или вызываемого типа, в таком случае нужна конкретизация внедряемого типа. Например, параметр метода указан с типом `mixed` или представлен составным типом (пересечение класс-типов, объединение типов), в этом случае атрибут будет конкретизировать какое значение контейнера необходимо внедрить в параметр.

Конфигурация атрибута:
- `\Attribute::TARGET_PARAMETER`
- `\Attribute::IS_REPEATABLE`

Сигнатура атрибута:

```php
\Kaspi\DiContainer\Attributes\Inject::__construct(
    string $id = ''
)
```

Параметры:
- `$id` - идентификатор контейнера [^ContainerId].

## Пример внедрение в параметр с типом mixed { #example }

Необходимо внедрить PHP класс `\App\Services\Bar` через конструктор в параметр `$entry` класса `\App\Services\Foo`.

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

use Kaspi\DiContainer\Attributes\Inject;// [!code ++]

final class Foo
{
    public function __construct(
        #[Inject(Bar::class)]// [!code ++]
        public readonly mixed $entry
    ) {}
}
```

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$foo = $container->get(Foo::class);// [!code highlight]

var_dump($foo->entry instanceof Bar);
// (bool) true
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
- [Внедрение зависимости по интерфейсу](../../cookbook/inject-interface.md#attribute-inject).
- [Внедрение зависимостей для параметров объединенного типа](../../cookbook/inject-union-type-params.md#php-attributes).

<!--@include: ../_include/term_notes.md-->
