---
outline: [2, 4]
---
# «Метод идентификатора» элемента коллекции по умолчанию { #key-from-method-default }

## Обзор { #overview }

Для элементов коллекции представленных PHP классом **«метод идентификатора» элемента коллекции по умолчанию** это резервный механизм получения идентификатора элемента коллекции если отсутствует конфигурация в [метаданных тега](../tag-metadata.md).

Если **«метод идентификатора» элемента коллекции по умолчанию** не реализован в PHP классе, то значение идентификатора будет представлено идентификатором контейнера [^ContainerId] этого элемента.

Внедрение коллекции через [хелпер функцию `diTaggedAs()`](../../php-definition/di-tagged-as.md) или [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md)
с указанием в параметре `$keyDefaultMethod` имени метода.

<!--@include: _include/key_from_method.md-->
<!--@include: _include/important_key_equals.md-->

## Пример использования «Метод идентификатора» элемента коллекции по умолчанию { #key-from-method-default-default }

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

    public static function getCustomIndex()
    {
        return 'baz_custom_id';    
    }
}
```

```php [Qux.php]
//file: /app/src/Services/Qux.php
namespace App\Services;

final class Baz
{
    // …
}
```

:::

Необходимо внедрить в параметр `\App\Services\Foo::$services` «ленивую коллекцию», где идентификаторы элементов коллекции будут получены из метаданных тега с именем `'key_as'`,
и предусмотреть резервный метод получения идентификатора элемента коллекции через **«метод идентификатора» элемента коллекции по умолчанию**.

### Настройка в файлах конфигураций { #key-from-method-default-default-php-definition }

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
                tag: 'tags.example_classes',
                key: 'key_as',
                keyDefaultMethod: 'getCustomIndex',
            )
        );

    yield diAutowire(Bar::class)
        ->bindTag(
            name: 'tags.example_classes',
            options: [
                'key_as' => 'id_bar',
            ],
        );
   
    yield diAutowire(Baz::class)
        // 🚩 не указываем метаданные тегу
        ->bindTag('tags.example_classes');
        
    yield diAutowire(Qux::class)
        // 🚩 не указываем метаданные тегу
        ->bindTag('tags.example_classes');
};
```

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar, Baz, Qux};

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

//string(6) "baz_custom_id"
//string(16) "App\Services\Baz"

//string(16) "App\Services\Qux"
//string(16) "App\Services\Qux"



// доступ к элементу коллекции через методы `\Psr\Container\ContainerInterface`
var_dump(
    $foo->services->has('id_bar'),
    $foo->services->get('id_bar') instanceof Bar,
);
// bool(true)
// bool(true)

// доступ к элементу коллекции через методы `\ArrayAccess`
var_dump(
    isset($foo->services['baz_custom_id']),
    $foo->services['baz_custom_id'] instanceof Baz,
);
// bool(true)
// bool(true)

// доступ к элементу коллекции через методы `\Psr\Container\ContainerInterface`
var_dump(
    $foo->services->has(Qux::class),
    $foo->services->get(Qux::class) instanceof Qux,
);
// bool(true)
// bool(true)
```

### Конфигурация через атрибуты { #key-from-method-default-default-attributes }


Для внедрения коллекции добавим [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) с параметром `$keyDefaultMethod`, к PHP классам которые будут элементам коллекции [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md) с метаданными.

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
            name: 'tags.example_classes',// [!code ++]
            key: 'key_as',// [!code ++]
            keyDefaultMethod: 'getCustomIndex',// [!code ++]
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
    name: 'tags.example_classes',// [!code ++]
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

// 🚩 не указываем метаданные тегу
#[Tag(name: 'tags.example_classes')]// [!code ++]
final class Baz
{
    // …

    public static function getCustomIndex()
    {
        return 'baz_custom_id';    
    }
}
```

```php [Qux.php]
//file: /app/src/Services/Qux.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

// 🚩 не указываем метаданные тегу
#[Tag(name: 'tags.example_classes')]// [!code ++]
final class Baz
{
    // …
}
```

:::

Контейнер зависимостей:

```php
use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\{Foo, Bar, Baz, Qux};

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

//string(6) "baz_custom_id"
//string(16) "App\Services\Baz"

//string(16) "App\Services\Qux"
//string(16) "App\Services\Qux"



// доступ к элементу коллекции через методы `\Psr\Container\ContainerInterface`
var_dump(
    $foo->services->has('id_bar'),
    $foo->services->get('id_bar') instanceof Bar,
);
// bool(true)
// bool(true)

// доступ к элементу коллекции через методы `\ArrayAccess`
var_dump(
    isset($foo->services['baz_custom_id']),
    $foo->services['baz_custom_id'] instanceof Baz,
);
// bool(true)
// bool(true)

// доступ к элементу коллекции через методы `\Psr\Container\ContainerInterface`
var_dump(
    $foo->services->has(Qux::class),
    $foo->services->get(Qux::class) instanceof Qux,
);
// bool(true)
// bool(true)

```
