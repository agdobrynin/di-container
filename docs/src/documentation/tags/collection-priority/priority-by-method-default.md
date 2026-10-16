---
outline: [2, 4]
---
# «Метод-приоритета» по умолчанию { #priority-by-method-default }

## Обзор { #overview }

Для случаев когда определить приоритет элемента коллекции из тега не удается, используется **«метод-приоритета» по умолчанию**.

При отсутствии в PHP классе **«метода-приоритета» по умолчанию**, элемент коллекции получит приоритет со значением `null`.

**«Метод-приоритета» по умолчанию** задаётся в параметре `$priorityDefaultMethod` [хелпер функции `diTaggedAs()`](../../php-definition/di-tagged-as.md) или в [атрибуте `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md).

<!--@include: _include/method_note.md-->

## Пример конфигурации { #example }

Необходимо внедрить коллекцию в параметр `\App\Services\Foo::$services`.
Элемент коллекции `\App\Services\Qux` должен использовать **«Метод-приоритета» по умолчанию**, для остальных элементов приоритет определить через **приоритет тега**.

Внедряемая коллекция должна получить следующий порядок элементов:
- `\App\Services\Qux`
- `\App\Services\Baz`
- `\App\Services\Bar`

PHP классы:

<!--@include: _include/code-group-src.md-->

Реализуем «метод-приоритета» по умолчанию в `App\Services\Qux::getPriorityForCollection()`. Метод возвращает значение приоритета `100`:

```php
// file: /app/src/Services/Qux.php
namespace App\Services;

final class Qux
{
    public static function getPriorityForCollection(): int // [!code ++]
    {// [!code ++]
        return 100;// [!code ++]
    }// [!code ++]

    // …
}
```


### Настройка в файлах конфигураций { #php-definition }

**«Метод-приоритета» по умолчанию** задаётся в параметре `$priorityDefaultMethod` [хелпер функции `diTaggedAs()`](../../php-definition/di-tagged-as.md).

Конфигурационный файл:

```php {12}
// file: /app/config/tagged_service.php
use Generator;
use App\Services\{Foo, Bar, Baz, Qux};

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs(
                'tags.ordered',
                priorityDefaultMethod: 'getPriorityForCollection',
            )
    );

    yield diAutowire(Bar::class)
        ->bindTag('tags.ordered');

    yield diAutowire(Baz::class)
        ->bindTag(
            name: 'tags.ordered',
            priority: 10,
        );

    yield diAutowire(Qux::class)
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

**«Метод-приоритета» по умолчанию** задаётся в параметре [атрибута `\Kaspi\DiContainer\Attributes\TaggedAs::$priorityDefaultMethod`](../../attribute-definition/tagged-as.md) через который внедряется зависимость.

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;// [!code ++]

final class Foo
{
    public function __construct(
        #[TaggedAs(// [!code ++]
            'tags.ordered',// [!code ++]
            priorityDefaultMethod: 'getPriorityForCollection',// [!code ++]
        )]// [!code ++]
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

#[Tag('tags.ordered')]// [!code ++]
final class Qux
{
    public static function getPriorityForCollection(): int
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

<!--@include: ../../php-definition/_include/bind-tag_notes.md-->
