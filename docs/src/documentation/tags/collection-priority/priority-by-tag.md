---
outline: [2, 4]
---
# Приоритет тега { #priority-by-tag }

## Обзор { #overview }

Приоритет тега задаётся в теге элемента коллекции.
[В файлах конфигураций](../../container-builder/configuration_files.md) определений приоритет указываться в параметре `$priority` метода `bindTag()`[^BindTag],
через PHP атрибуты приоритет указывается в параметре [атрибута `\Kaspi\DiContainer\Attributes\Tag::$priority`](../../attribute-definition/tag.md).

## Пример конфигурации { #example-priority-by-tag }

Необходимо внедрить коллекцию в параметр `\App\Services\Foo::$services` в определенном порядке:
- `\App\Services\Qux`
- `\App\Services\Baz`
- `\App\Services\Bar`

PHP классы:

::: code-group

```php [Foo.php]
// file: /app/src/Services/Foo.php
namespace App\Services;

final class Foo
{
    public function __construct(public readonly iterable $services) {}    
}
```

```php [Bar.php]
// file: /app/src/Services/Bar.php
namespace App\Services;

final class Bar
{
    // …
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Services;

final class Baz
{
    // …
}
```

```php [Qux.php]
// file: /app/src/Services/Qux.php
namespace App\Services;

final class Qux
{
    // …
}
```

:::

### Настройка в файлах конфигураций { #priority-by-tag-php-definition }

Передаем значение приоритета в параметре `$priority` метода `bindTag()`[^BindTag]

Конфигурационный файл:

```php
// file: /app/config/tagged_foo_service.php
use Generator;
use App\Services\{Foo, Bar, Baz};

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs('tags.ordered')
        );

    yield diAutowire(Bar::class)
        ->bindTag(name: 'tags.ordered');
   
   yield diAutowire(Baz::class)
        ->bindTag(
            name: 'tags.ordered',
            priority: 10,
        );

   yield diAutowire(Qux::class)
        ->bindTag(
            name: 'tags.ordered',
            priority: 100,
        );
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
// вывод в консоли:
//string(16) "App\Services\Qux"
//string(16) "App\Services\Baz"
//string(16) "App\Services\Bar"
```

Порядок сортировки внедрённой коллекции обусловлен значениями `$prioriy` для каждого тега.

### Конфигурация через атрибуты { #priority-by-tag-attributes }

Для внедрения коллекции добавим [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md),
к PHP классам которые будут элементам коллекции [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md).

PHP классы:

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

#[Tag(name: 'tags.ordered')]// [!code ++]
final class Bar
{
    // …
}
```

```php [Baz.php]
// file: /app/src/Services/Baz.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(name: 'tags.ordered', priority: 10)]// [!code ++]
final class Baz
{
    // …
}
```

```php [Qux.php]
// file: /app/src/Services/Qux.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(name: 'tags.ordered', priority: 100)]// [!code ++]
final class Qux
{
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
// вывод в консоли:
//string(16) "App\Services\Qux"
//string(16) "App\Services\Baz"
//string(16) "App\Services\Bar"
```

Порядок сортировки внедрённой коллекции обусловлен значениями в атрибутах `\Kaspi\DiContainer\Attributes\Tag::$prioriy` для каждого тега.

<!--@include: ../../php-definition/_include/bind-tag_notes.md-->
