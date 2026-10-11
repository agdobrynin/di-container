# 🐘 Конфигурирование тегов в стиле php определений { #title }

## Обзор { #overview }

Для указания тегов используется метод `bindTag()` доступный в хелпер функциях [`diAutowire()`](../php-definition/di-autowire.md#bindtag),
[`diAutowire()`](../php-definition/di-autowire.md#bindtag), [`diCallable()`](../php-definition/di-callable.md#bindtag), [`diProxyClosure()`](../php-definition/di-proxy-closure.md#bindtag), [`diValue()`](../php-definition/di-value.md#bindtag).

Метод `bindTag()` описан интерфейсом `\Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface`.

Сигнатура метода:

```php
DiDefinitionTagArgumentInterface::bindTag(
    string $name,
    array $options = [],
    null|int|string $priority = null
)
```

Параметры:
- `$name` – имя тега.
- `$options` – [метаданные тега](tag-metadata.md).
- `$priority` – [приоритет элемента в коллекции](collection-priority.md).

> [!TIP]
> Ознакомьтесь с разделом «[Рекомендации по именованию значений для имён тегов, и метаданных для тега](naming-convention.md)».

## Внедрение коллекции { #inject-by-di-tagged-as }

[Хелпер функция `diTaggedAs()`](../php-definition/di-tagged-as.md) внедряет коллекцию элементов из контейнера отмеченных тегом.

[Ознакомьтесь с примером конфигурирования](../php-definition/di-tagged-as.md#example).


Указывайте значение `false` для параметра `$isLazy` в хелпер функции `diTaggedAs()` при внедрении коллекции в параметр с типом `array`:

:::code-group

```php [tagged_services.php]
// file: /app/config/tagged_services.php
use Generator;
use App\Services\Foo;

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            diTaggedAs('tags.group_1', isLazy: false)
        );
};
```

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

class Foo
{
    public function __construct(public readonly array $collection) {}

}
```

:::

Внедрение коллекции также доступно для параметров переменной длины:

::: code-group

```php [tagged_services.php]
// file: /app/config/tagged_services.php
use Generator;
use App\Services\Bar;

use function Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Bar::class)
        ->bindArguments(
            srvGroup_1: diTaggedAs('tags.group_1'),
            srvGroup_2: diTaggedAs('tags.group_2'),
        );
};
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

class Bar
{
    public function __construct(iterable ...$srvGroup) {}

}
```

:::

> [!TIP]
> При использовании [именованных аргументов](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments)
> и [параметров переменной длины](https://www.php.net/manual/ru/functions.arguments.php#functions.variable-arg-list)
> действуют правила описанные в документации по PHP.
