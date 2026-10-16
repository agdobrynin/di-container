---
outline: [2, 4]
---
# Идентификатор элемента в метаданных тега { #key-in-metadata }

## Обзор { #overview } 

Пользовательские идентификаторы элементам коллекции можно установить через [метаданные](../tag-metadata.md) тега.

Значение в метаданных тега указывается парой ключ-значение:

- **ключ** – имя метаданных, одинаковое во всех тегах коллекции.
- **значение** – идентификатор элемента коллекции. 

Внедрение коллекции через [хелпер функцию `diTaggedAs()`](../../php-definition/di-tagged-as.md) или [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md)
с передачей **имени метаданных** в параметре `$key`.

Метаданные тега передаются в параметре `$options` метода `bindTag()` [^BindTag] или [атрибута `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md).

<!--@include: _include/important_key_equals.md-->

## Пример использования идентификатора элемента из метаданных { #example-key-in-metadata }

Необходимо внедрить в параметр `\App\Services\Foo::$services` «ленивую коллекцию», где идентификаторы элементов коллекции будут получены из метаданных тега с именем `'key_as'`.

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

### Настройка в файлах конфигураций { #example-key-in-metadata-php-definition }

Внедрение в параметр `\App\Services\Foo::$services` через [хелпер функцию `diTaggedAs()`](../../php-definition/di-tagged-as.md) и передачей имени метаданных в параметр `$key`.

PHP классам добавим теги с метаданными через метод `bindTag()` [^BindTag].

Конфигурационный файл:

```php {12,19-21,27-29}
// file: /app/config/tagged_foo_service.php
use Generator;
use App\Services\{Foo, Bar, Baz};

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs(
                tag: 'tags.key_from_metadata',
                key: 'key_as',
            )
        );

    yield diAutowire(Bar::class)
        ->bindTag(
            name: 'tags.key_from_metadata',
            options: [
                'key_as' => 'id_bar',
            ],
        );
   
   yield diAutowire(Baz::class)
        ->bindTag(
            name: 'tags.key_from_metadata'
            options: [
                'key_as' => 'id_baz',
            ],
        );
};
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar, Baz};

$container = (new DiContainerBuilder())
    ->load('/app/config/tagged_foo_service.php')
    ->build();

$foo = $container->get(Foo::class);

// перебор коллекции
foreach ($foo->services as $id => $service) {
    var_dump($id, $service::class);
}
// вывод в консоли:
//string(6) "id_bar"
//string(16) "App\Services\Bar"
//string(6) "id_baz"
//string(16) "App\Services\Baz"


// доступ к элементу коллекции через методы `\Psr\Container\ContainerInterface`
var_dump(
    $foo->services->has('id_bar'),
    $foo->services->get('id_bar') instanceof Bar,
);
// bool(true)
// bool(true)

// доступ к элементу коллекции через методы `\ArrayAccess`
var_dump(
    isset($foo->services['id_baz']),
    $foo->services['id_baz'] instanceof Baz,
);
// bool(true)
// bool(true)
```

### Конфигурация через атрибуты { #example-key-in-metadata-attributes }

Внедрение коллекции через [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) и передачей имени метаданных в параметр `$key`.

К PHP классам которые будут элементам коллекции применим [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md) с метаданными.

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
            name: 'tags.key_from_metadata',// [!code ++]
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
    name: 'tags.key_from_metadata',// [!code ++]
    options: [// [!code ++]
        'key_as' => 'id_bar',// [!code ++]
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
    name: 'tags.key_from_metadata',// [!code ++]
    options: [// [!code ++]
        'key_as' => 'id_baz',// [!code ++]
    ],// [!code ++]
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
use App\Services\{Foo, Bar, Baz};

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')
    ->build();

$foo = $container->get(Foo::class);

// перебор коллекции
foreach ($foo->services as $id => $service) {
    var_dump($id, $service::class);
}
// вывод в консоли:
//string(6) "id_bar"
//string(16) "App\Services\Bar"
//string(6) "id_baz"
//string(16) "App\Services\Baz"


// доступ к элементу коллекции через методы `\Psr\Container\ContainerInterface`
var_dump(
    $foo->services->has('id_bar'),
    $foo->services->get('id_bar') instanceof Bar,
);
// bool(true)
// bool(true)

// доступ к элементу коллекции через методы `\ArrayAccess`
var_dump(
    isset($foo->services['id_baz']),
    $foo->services['id_baz'] instanceof Baz,
);
// bool(true)
// bool(true)
```
<!--@include: ../../php-definition/_include/bind-tag_notes.md-->
