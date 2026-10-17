---
outline: [2, 4]
---
# «Метод-приоритета» { #priority-by-method }

## Обзор { #overview }

Для элементов внедряемой коллекции представленных PHP классом приоритет в коллекции может быть задан через «метод-приоритета».

«**Метод-приоритета**» задаётся в теге элемента коллекции.

<!--@include: _include/method_note.md-->

## Пример конфигурации { #example }

Необходимо внедрить коллекцию в параметр `\App\Services\Foo::$services`.
Элемент коллекции `\App\Services\Qux` должен использовать «**метод-приоритета**», для остальных элементов приоритет определить через **приоритет тега**.

Внедряемая коллекция должна получить следующий порядок элементов:
- `\App\Services\Qux`
- `\App\Services\Baz`
- `\App\Services\Bar`

PHP классы:

<!--@include: _include/code-group-src.md-->

Реализуем «метод-приоритета» в `App\Services\Qux::getPriority()`. Метод возвращает значение приоритета `100`:


```php
// file: /app/src/Services/Qux.php
namespace App\Services;

final class Qux
{
    public static function getPriority(): int // [!code ++]
    {// [!code ++]
        return 100;// [!code ++]
    }// [!code ++]

    // …
}
```

### Настройка в файлах конфигураций { #php-definition }

«Метод-приоритета» передаётся через [метаданные](../tag-metadata.md) в параметре `$options` метода `bindTag()`[^BindTag].
**Имя метаданных должно быть** представлено строкой `'priority.method'`, значение – имя метода.


Конфигурационный файл:

```php {16-18}
// file: /app/config/tagged_service.php
use Generator;
use App\Services\{Foo, Bar, Baz, Qux};

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs('tags.ordered')
    );

    yield diAutowire(Qux::class)
        ->bindTag(
            name: 'tags.ordered',
            options: [
               'priority.method' => 'getPriority',
            ]
        );
        
    yield diAutowire(Baz::class)
        ->bindTag(
            name: 'tags.ordered',
            priority: 10,
        );
        
    yield diAutowire(Bar::class)
        // по умолчанию `$priority = null`
        ->bindTag('tags.ordered');
};
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/tagged_foo_service.php')
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $service) {
    var_dump($service::class);
}
// вывод консоли:
//string(16) "App\Services\Qux"
//string(16) "App\Services\Baz"
//string(16) "App\Services\Bar"
```

### Конфигурация через атрибуты { #attributes }

«Метод-приоритета» указывается в параметре атрибута `\Kaspi\DiContainer\Attributes\Tag::$priorityMethod`.

Внедрение коллекции через [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md).

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;// [!code ++]

final class Foo
{
    public function __construct(
        #[TaggedAs('tags.ordered')]// [!code ++]
        public readonly iterable $services
    ) {}    
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag('tags.ordered')]// [!code ++]
final class Bar
{
    // …
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag('tags.ordered', priority: 10)]// [!code ++]
final class Baz
{
    // …
}
```

```php [Qux.php]
// file: /app/src/Services/Qux.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag('tags.ordered', priorityMethod: 'getPriority')]// [!code ++]
final class Qux
{
    public static function getPriority(): int
    {
        return 100;
    }

    // …
}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$foo = $container->get(Foo::class);

foreach ($foo->services as $service) {
    var_dump($service::class);
}
// вывод консоли:
//string(16) "App\Services\Qux"
//string(16) "App\Services\Baz"
//string(16) "App\Services\Bar"
```

<!--@include: ../../php-definition/_include/bind_notes.md-->
