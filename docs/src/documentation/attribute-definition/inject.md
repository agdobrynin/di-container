---
outline: [2,4]
title: Атрибут Inject
---
### Атрибут Inject

Атрибут `\Kaspi\DiContainer\Attributes\Inject` внедряет зависимость через идентификатор контейнера [^ContainerId], это эквивалентно вызову метода контейнера `get()`.

В тех случаях, когда параметр метода или вызываемого типа[^CallableType] задан с использованием пользовательских типов (например – классов PHP, встроенных классов PHP или перечислений), использовать атрибут `Inject` не требуется — контейнер автоматически внедрит зависимость на основе конфигурации.

Атрибут `Inject` необходим в тех случаях, когда контейнер не может автоматически определить тип зависимости для конкретного параметра метода или вызываемого типа, в таких ситуациях тип внедряемого объекта должен быть указан явно. Например, если параметр метода определен как `mixed` или как составной тип (пересечение или объединение типов), этот атрибут позволяет точно указать, какое именно значение из контейнера следует внедрить в данный параметр.

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

### Пример внедрение в параметр с типом mixed { #example }

PHP-классы:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

final class Foo
{
    public function __construct(
        public readonly mixed $entry
    ) {}
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

Необходимо внедрить в параметр конструктора `$entry` PHP-класса `\App\Services\Foo` объект класса `\App\Services\Bar`.

Параметр конструктора `$entry` PHP-класса `\App\Services\Foo` представлен типом `mixed` поэтому контейнер не сможет самостоятельно решать какой тип зависимости необходимо внедрять в этот параметр. Конкретизируем через атрибут `\Kaspi\DiContainer\Attributes\Inject` объект необходимый для внедрения.

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

::: 

::: code-group

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

$foo = $container->get(Foo::class);

var_dump($foo->entry instanceof Bar);
```
:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
(bool) true
```

### Рецепты { #cookbook }

В разделе «Рецепты» представлены примеры конкретизации объявления внедрения параметров:
- [Внедрение зависимости PHP-класса реализующего конкретный интерфейс](../../cookbook/inject-interface/arg.md#inject).
- [Внедрение зависимостей для параметров объединенного типа](../../cookbook/inject-union-type-params.md#php-attributes).

<!--@include: ../_include/term_notes.md-->
