---
outline: [2, 4]
---
# Совпадение идентификаторов элементов в коллекции { #keys-equals }

## Обзор { #overview }

Внедряемая коллекция по умолчанию в качестве идентификатора элементов получает идентификатор контейнера [^ContainerId] этого элемента,
но через [метаданные тега](../tag-metadata.md) можно указывать пользовательские идентификаторы элементов коллекции.

При совпадении идентификаторов у элементов в коллекцию попадает элемент с более высоким [приоритетом](../collection-priority/index.md), другие элементы с таким же идентификатором будут игнорированы.

## Пример { #keys-equals-example }

Необходимо внедрить в параметр `\App\Services\Foo::$services` «ленивую коллекцию», где идентификаторы элементов будут получены из метаданных тегов.

PHP классы:

::: code-group

```php [Foo.php]
//file: /app/src/Services/Foo.php
namespace App\Services;

final class Foo
{
    public function __construct(
        public readonly iterable $services
    ) {}
}
```

```php [Bar.php]
//file: /app/src/Services/Bar.php
namespace App\Services;

final class Bar
{
    // …
}
```

```php [Baz.php]
//file: /app/src/Services/Baz.php
namespace App\Services;

final class Baz
{
    // …
}
```

:::

### Настройка в файлах конфигураций { #keys-equals-example-php-definition }

Для внедрения коллекции используем [хелпер функцию `diTaggedAs()`](../../php-definition/di-tagged-as.md), добавим теги и метаданные PHP классам через метод `bindTag()` [^BindTag].

Конфигурационный файл:

```php
// file: /app/config/tagged_foo_service.php
use Generator;
use App\Services\{Foo, Bar, Baz, Qux};

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs(
                tag: 'tags.key_equals',
                key: 'key_as',
            )
        );

    yield diAutowire(Bar::class)
        ->bindTag(
            name: 'tags.key_equals',
            options: [
                'key_as' => 'id_foo',// [!code warning]
            ],
            // priority: null, значение по умолчанию
        );
   
    yield diAutowire(Baz::class)
        ->bindTag(
            name: 'tags.key_equals',
            options: [
                'key_as' => 'id_foo',// [!code warning]
            ],
            priority: 100,// [!code warning]
        );
};
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Baz};

$container = (new DiContainerBuilder())
    ->load('/app/config/tagged_foo_service.php')
    ->build();

$foo = $container->get(Foo::class);

// подсчёт количества элементов коллекции через `\Countable`
var_dump(count($foo->services));
// int(1)

// перебор коллекции
foreach ($foo->services as $id => $service) {
    var_dump($id, $service::class);
}
// вывод в консоли:
//string(6) "id_foo"
//string(16) "App\Services\Baz"

// доступ к элементу коллекции через методы `\Psr\Container\ContainerInterface`
var_dump(
    $foo->services->has('id_foo'),
    $foo->services->get('id_foo') instanceof Bar,
);
// bool(true)
// bool(false)
```

### Конфигурация через атрибуты { #keys-equals-example-attributes }

Для внедрения коллекции добавим [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) с указанием ключа `'key_as'` из метаданных тегов, к PHP классам которые будут элементам коллекции [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md) с метаданными.

PHP классы:

::: code-group

```php [Foo.php]
//file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;// [!code ++]

final class Foo
{
    public function __construct(
        #[TaggedAs(// [!code ++]
            name: 'tags.key_equals',// [!code ++]
            key: 'key_as',// [!code ++]
        )]// [!code ++]
        public readonly iterable $services
    ) {}    
}
```

```php [Bar.php]
//file: /app/src/Services/Bar.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(// [!code ++]
    name: 'tags.key_equals',// [!code ++]
    options: [// [!code ++]
        'key_as' => 'id_foo',// [!code ++]
    ],// [!code ++]
)]// [!code ++]
final class Bar
{
    // …
}
```

```php [Baz.php]
//file: /app/src/Services/Baz.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(// [!code ++]
    name: 'tags.key_equals',// [!code ++]
    options: [// [!code ++]
        'key_as' => 'id_foo',// [!code ++]
    ],// [!code ++]
    priority: 100,// [!code ++]
)]// [!code ++]
final class Baz
{
    // …
}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Baz};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$foo = $container->get(Foo::class);

// подсчёт количества элементов коллекции через `\Countable`
var_dump(count($foo->services));
// int(1)

// перебор коллекции
foreach ($foo->services as $id => $service) {
    var_dump($id, $service::class);
}
// вывод в консоли:
//string(6) "id_foo"
//string(16) "App\Services\Baz"
```

<!--@include: ../../_include/term_notes.md-->
<!--@include: ../../php-definition/_include/bind-tag_notes.md-->