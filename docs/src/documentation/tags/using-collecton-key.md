---
outline: [2, 4]
---
# 🔑 Идентификаторы элементов коллекции { #title }

## Обзор { #overview }

Внедряемая коллекция по умолчанию в качестве идентифкаторов элементов получает идентификатор контейнера [^ContainerId] этого элемента, что позволяет обращаться к элементам коллекции в [произвольном порядке](#key-access).

Через настройки внедрения коллекции и [метаданные тега](tag-metadata.md) можно указывать пользовательские идентификаторы элементам коллекции:
- [Идентификатор элемента как целое число](#key-as-int).
- [Идентификатор элемента в метаданных тега](#key-in-metadata).
- [«Метод идентификатора» элемента коллекции](#key-from-method) для элемента коллекции PHP класс.
- [«Метод идентификатора» элемента коллекции по умолчанию](#key-from-method-default) для элемента коллекции PHP класс.

> [!WARNING]
> При обращении к элементу коллекции по строковому идентификатору может быть выброшено исключение с типом `Psr\Container\NotFoundExceptionInterface`, если элемент коллекции не найден.

<!--@include: _include/key_from_method.md-->

> [!IMPORTANT]
> Если в коллекции встречаются одинаковые идентификаторы элементов, то в коллекцию попадёт элемент с более высоким приоритетом, определения с таким же значением идентификатора будут игнорированы.
> Изучите раздел «[Совпадение идентификаторов элементов в коллекции](#keys-equals)».

## Произвольный доступ к элементам коллекции { #key-access }

При внедрении коллекции через [хелпер функцию `diTaggedAs()`](../php-definition/di-tagged-as.md) или [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md) параметр `$isLazy` по умолчанию имеет значение `true`,
что означает – внедряемый объект будет «[ленивой коллекцией](index.md#collection-is-lazy)».

«Ленивая коллекция» реализует интерфейсы:
- `\Iterator`
- `\Psr\Container\ContainerInterface`
- `\ArrayAccess`
- `\Countable`

Реализация этих интерфейсов даёт возможность:
- Перебор элементов коллекции через [языковую конструкцию `foreach`](https://www.php.net/manual/en/control-structures.foreach.php).
- Доступ к элементу по идентификатору в стиле PHP массивов – `$service['foo.name']`.
- Доступ к элементу через методы `get()` и `has()` описанные в [PSR-11](https://www.php-fig.org/psr/psr-11/).

### Пример внедрения коллекции { #example-inject-lazy-collection-with-key-assess }

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

Необходимо внедрить в параметр `\App\Services\Foo::$services` «ленивую коллекцию» с доступом к элементам коллекции по идентификатору контейнера [^ContainerId].

#### Настройка в файлах конфигураций { #example-inject-lazy-collection-with-key-assess-php-definitions }

Для внедрения коллекции используем [хелпер функцию `diTaggedAs()`](../php-definition/di-tagged-as.md), добавим теги PHP классам через метод `bindTag()` [^BindTag].

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
                tag: 'tags.access_by_key'
            )
        );

    yield diAutowire(Bar::class)
        ->bindTag(name: 'tags.access_by_key');
   
   yield diAutowire(Baz::class)
        ->bindTag(name: 'tags.access_by_key');
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
    var_dump($id, is_object($service));
}
// вывод в консоли:
//string(16) "App\Services\Bar"
// bool(true)
//string(16) "App\Services\Baz"
// bool(true)


// доступ к элементу коллекции через методы `\Psr\Container\ContainerInterface`
var_dump(
    $foo->services->has(Bar::class),
    $foo->services->get(Bar::class) instanceof Bar,
);
// bool(true)
// bool(true)

// доступ к элементу коллекции через методы `\ArrayAccess`
var_dump(
    isset($foo->services[Baz::class]),
    $foo->services[Baz::class] instanceof Baz,
);
// bool(true)
// bool(true)

// подсчёт количества элементов коллекции через `\Countable`
var_dump(count($foo->services));
// int(2)
```

#### Конфигурация через атрибуты { #example-inject-lazy-collection-with-key-assess-attributes }

Для внедрения коллекции добавим [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md),
к PHP классам которые будут элементам коллекции [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md).

PHP классы:

::: code-group

```php [Foo.php]
//file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;// [!code ++]

final class Foo
{
    public function __construct(
        #[TaggedAs('tags.access_by_key')]// [!code ++]
        public readonly iterable $services
    ) {}    
}
```

```php [Bar.php]
//file: /app/src/Services/Bar.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(name: 'tags.access_by_key')]// [!code ++]
final class Bar
{
    // …
}
```

```php [Baz.php]
//file: /app/src/Services/Baz.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(name: 'tags.access_by_key')]// [!code ++]
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
    var_dump($id, is_object($service));
}
// вывод в консоли:
//string(16) "App\Services\Bar"
// bool(true)
//string(16) "App\Services\Baz"
// bool(true)


// доступ к элементу коллекции через методы `\Psr\Container\ContainerInterface`
var_dump(
    $foo->services->has(Bar::class),
    $foo->services->get(Bar::class) instanceof Bar,
);
// bool(true)
// bool(true)

// доступ к элементу коллекции через методы `\ArrayAccess`
var_dump(
    isset($foo->services[Baz::class]),
    $foo->services[Baz::class] instanceof Baz,
);
// bool(true)
// bool(true)

// подсчёт количества элементов коллекции через `\Countable`
var_dump(count($foo->services));
// int(2)
```

## Идентификатор элемента как целое число { #key-as-int }

Внедряемая коллекция может быть представлен простым списком, где идентификаторы элементов представлены целыми числами от `0` до `n`.

В [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md) указать значение `false` в параметре `$useKeys`:

```php
// file: /app/config/tagged_foo_service.php
use Generator;
use App\Services\{Foo, Bar, Baz};

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs(
                tag: 'tags.access_by_key',
                useKeys: false,// [!code ++]
            )
        );

    // ...
};
```

Указать значение `false` в параметре [атрибута `\Kaspi\DiContainer\Attributes\TaggedAs::$useKeys`](../attribute-definition/tagged-as.md):

```php
//file: /app/src/Services/Foo.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\TaggedAs;

final class Foo
{
    public function __construct(
        #[TaggedAs(
            name: 'tags.access_by_key',
            useKeys: false,// [!code ++]
        )]
        public readonly iterable $services
    ) {}    
}
```

## Идентификатор элемента в метаданных тега { #key-in-metadata }

Пользовательские идентификаторы элементам коллекции можно установить через [метаданные](tag-metadata.md) тега.

Значение в метаданных тега указывается парой ключ-значение:
- **ключ** – произвольная непустая строка, одинаковая во всех тегах коллекции.
- **значение** – не пустая строка уникальная в рамках коллекции.

Для внедрения коллекции указываем значение ключа из метаданных тегов в параметре `$key` [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md) или [атрибуте `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md).

Указываем метаданные тегов через параметр `$options` в методе `bindTag()` [^BindTag] или [атрибуте `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md).

### Пример использования идентификатора элемента из метаданных { #example-key-in-metadata }

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
Для получения идентификатора элемента из [метаданных тега](tag-metadata.md) будем указывать значение `'key_as'`.


#### Настройка в файлах конфигураций { #example-key-in-metadata-php-definition }

Для внедрения коллекции используем [хелпер функцию `diTaggedAs()`](../php-definition/di-tagged-as.md), добавим теги и метаданные PHP классам через метод `bindTag()` [^BindTag].

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

#### Конфигурация через атрибуты { #example-key-in-metadata-attributes }

Для внедрения коллекции добавим [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md),
к PHP классам которые будут элементам коллекции [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md) с метаданными.

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

## «Метод идентификатора» элемента коллекции { #key-from-method }

Идентификатор элемента коллекции, представленного PHP классом, можно получить через специальный метод этого PHP класса – «метод идентификатора». Настройка производится через [метаданные тега](tag-metadata.md).

Значение в метаданных тега указывается парой ключ-значение:
- **ключ** – произвольная непустая строка, одинаковая во всех тегах коллекции.
- **значение** – имя «метода идентификатора» с префиксом `self::`.

Для внедрения коллекции указываем значение ключа из метаданных тегов в параметре `$key` [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md) или [атрибуте `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md).

Указываем метаданные тегов через параметр `$options` в методе `bindTag()` [^BindTag] или [атрибуте `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md).

<!--@include: _include/key_from_method.md-->

### Пример использования «Метод идентификатора» элемента коллекции { #example-key-from-method }

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

    public static function getKeyOrClassName(string $tag): string
    {
        return match ($tag) {
            'tags.key_from_method' => 'id_bar',
            default => self::class
        };
    }
}
```

```php [Baz.php]
//file: /app/src/Services/Baz.php
namespace App\Services;

final class Baz
{
    // …
    
    public static function getKeyThroughOptions(string $tag, array $options): string
    {
        $isStaging = $options['app_staging'] ?? false;

        if (true === $isStaging) {
            return 'id_baz_staging';
        }
        
        return 'id_baz';
    }
}
```

:::

Необходимо внедрить в параметр `\App\Services\Foo::$services` «ленивую коллекцию», где идентификаторы элементов коллекции будут получены из «**метода идентификатора**» каждого элемента коллекции. Ключ в [метаданных тегов](tag-metadata.md) указывающий какой метод вызвать – `'key_as'`.

#### Настройка в файлах конфигураций { #example-key-from-method-php-definitions }

Для внедрения коллекции используем [хелпер функцию `diTaggedAs()`](../php-definition/di-tagged-as.md), добавим теги и метаданные PHP классам через метод `bindTag()` [^BindTag].

```php
// file: /app/config/tagged_foo_service.php
use Generator;
use App\Services\{Foo, Bar, Baz};

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs(
                tag: 'tags.key_from_method',
                key: 'key_as',
            )
        );

    yield diAutowire(Bar::class)
        ->bindTag(
            name: 'tags.key_from_method',
            options: [
                'key_as' => 'self::getKeyOrClassName',
            ],
        );
        
   yield diAutowire(Baz::class)
        ->bindTag(
            name: 'tags.key_from_method'
            options: [
                'key_as' => 'self::getKeyThroughOptions',
                'app_staging' => true,
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
//string(6) "id_baz_staging"
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
    isset($foo->services['id_baz_staging']),
    $foo->services['id_baz_staging'] instanceof Baz,
);
// bool(true)
// bool(true)
```

#### Конфигурация через атрибуты { #example-key-from-method-php-attributes }

Для внедрения коллекции добавим [атрибут \Kaspi\DiContainer\Attributes\TaggedAs](../attribute-definition/tagged-as.md), к PHP классам которые будут элементам коллекции [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md) с метаданными.

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
            name: 'tags.key_from_method',// [!code ++]
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
    name: 'tags.key_from_method',// [!code ++]
    options: [// [!code ++]
        'key_as' => 'self::getKeyOrClassName',// [!code ++]
    ],// [!code ++]
)]// [!code ++]
final class Bar
{
    // …

    public static function getKeyOrClassName(string $tag): string
    {
        return match ($tag) {
            'tags.key_from_method' => 'id_bar',
            default => self::class
        };
    }
}
```

```php [Baz.php]
//file: /app/src/Services/Baz.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag(// [!code ++]
    name: 'tags.key_from_method',// [!code ++]
    options: [// [!code ++]
        'key_as' => 'self::getKeyThroughOptions',// [!code ++]
    ],// [!code ++]
)]// [!code ++]
final class Baz
{
    // …
    
    public static function getKeyThroughOptions(string $tag, array $options): string
    {
        $isStaging = $options['app_staging'] ?? false;

        if (true === $isStaging) {
            return 'id_baz_staging';
        }
        
        return 'id_baz';
    }
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

## «Метод идентификатора» элемента коллекции по умолчанию { #key-from-method-default }

Для элементов коллекции представленных PHP классом **«метод идентификатора» элемента коллекции по умолчанию** это резервный механизм получения идентификатора элемента коллекции если отсутствует конфигурация в [метаданных тега](tag-metadata.md). 

Если **«метод идентификатора» элемента коллекции по умолчанию** не реализован в PHP классе, то значение идентификатора будет представлено идентификатором контейнера [^ContainerId] этого элемента. 

Внедрение коллекции через [хелпер функцию `diTaggedAs()`](../php-definition/di-tagged-as.md) или [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md)
с указанием в параметре `$keyDefaultMethod` имени метода.

<!--@include: _include/key_from_method.md-->

### Пример использования «Метод идентификатора» элемента коллекции по умолчанию { #key-from-method-default-default }

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

#### Настройка в файлах конфигураций { #key-from-method-default-default-php-definition }

Для внедрения коллекции используем [хелпер функцию `diTaggedAs()`](../php-definition/di-tagged-as.md), добавим теги и метаданные PHP классам через метод `bindTag()` [^BindTag].

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

#### Конфигурация через атрибуты { #key-from-method-default-default-attributes }


Для внедрения коллекции добавим [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md) с параметром `$keyDefaultMethod`, к PHP классам которые будут элементам коллекции [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md) с метаданными.

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

## Совпадение идентификаторов элементов в коллекции { #keys-equals }

Внедряемая коллекция по умолчанию в качестве идентификатора элементов получает идентификатор контейнера [^ContainerId] этого элемента,
но через [метаданные тега](tag-metadata.md) можно указывать пользовательские идентификаторы элементов коллекции.

При совпадении идентификаторов у элементов в коллекцию попадает элемент с более высоким [приоритетом](collection-priority.md), другие элементы с таким же идентификатором будут игнорированы.

### Пример { #keys-equals-example }

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

Необходимо внедрить в параметр `\App\Services\Foo::$services` «ленивую коллекцию», где идентификаторы элементов будут получены из метаданных тегов.

#### Настройка в файлах конфигураций { #keys-equals-example-php-definition }

Для внедрения коллекции используем [хелпер функцию `diTaggedAs()`](../php-definition/di-tagged-as.md), добавим теги и метаданные PHP классам через метод `bindTag()` [^BindTag].

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
                'key_as' => 'id_foo',
            ],
            // priority: null, значение по умолчанию
        );
   
    yield diAutowire(Baz::class)
        ->bindTag(
            name: 'tags.key_equals',
            options: [
                'key_as' => 'id_foo',
            ],
            priority: 100,
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

#### Конфигурация через атрибуты { #keys-equals-example-attributes }

Для внедрения коллекции добавим [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md) с указанием ключа `'key_as'` из метаданных тегов, к PHP классам которые будут элементам коллекции [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md) с метаданными.

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

<!--@include: ../_include/term_notes.md-->
<!--@include: ../php-definition/_include/bind-tag_notes.md-->
