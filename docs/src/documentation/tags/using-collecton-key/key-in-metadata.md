---
outline: [2, 4]
---
# Идентификатор элемента в метаданных тега { #key-in-metadata }

## Обзор { #overview } 

Пользовательские идентификаторы элементам коллекции можно установить через [метаданные](../tag-metadata.md) тега.

Значение в метаданных тега указывается парой ключ-значение:
- **ключ** – произвольная непустая строка, одинаковая во всех тегах коллекции.
- **значение** – не пустая строка уникальная в рамках коллекции.

Для внедрения коллекции указываем значение ключа из метаданных тегов в параметре `$key` [хелпер функции `diTaggedAs()`](../../php-definition/di-tagged-as.md) или [атрибуте `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md).

Указываем метаданные тегов через параметр `$options` в методе `bindTag()` [^BindTag] или [атрибуте `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md).

<!--@include: _include/important_key_equals.md-->

## Пример использования идентификатора элемента из метаданных { #example-key-in-metadata }

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

Необходимо внедрить в параметр `\App\Services\Foo::$services` «ленивую коллекцию», где идентифакаторы элементов коллекции будут получены из метаданных каждого тега.
Для получения идентификатора элемента из [метаданных тега](../tag-metadata.md) будем указывать значение `'key_as'`.


### Настройка в файлах конфигураций { #example-key-in-metadata-php-definition }

Для внедрения коллекции используем [хелпер функцию `diTaggedAs()`](../../php-definition/di-tagged-as.md), добавим теги и метаданные PHP классам через метод `bindTag()` [^BindTag].

Конфигурационный файл:

```php
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

Для внедрения коллекции добавим [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md),
к PHP классам которые будут элементам коллекции [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md) с метаданными.

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
