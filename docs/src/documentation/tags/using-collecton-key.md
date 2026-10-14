---
outline: [2, 4]
---
# 🔑 Использование ключей коллекции { #title }

## Обзор { #overview }

Внедряемая коллекция по умолчанию в качестве ключей элементов получает идентификатор контейнера [^ContainerId] этого элемента, что позволяет обращаться к элементам коллекции в [произвольном порядке](#key-access).

Это поведение можно изменить:
- через [отключение строковых ключей в коллекции](#key-as-int).
- через [метаданные тега](#key-in-metadata).
- через [«Метод ключа» элемента коллекции](#key-from-method) для элемента коллекции PHP класс.
- через [«Метод ключа» элемента коллекции по умолчанию](#key-from-method-default) для элемента коллекции PHP класс.

> [!WARNING]
> При обращении к элементу коллекции по ключу может быть выброшено исключение с типом `Psr\Container\NotFoundExceptionInterface`, если элемент коллекции не найден по запрошенному ключу.

<!--@include: _include/key_from_method.md-->

> [!IMPORTANT]
> Если в коллекции встречаются одинаковые ключи у элементов, то в коллекцию попадёт элемент с более высоким приоритетом, определения с таким же значением ключа будут игнорированы.
> Изучите раздел «[Совпадение ключей элементов в коллекции](#keys-equals)».


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
- Доступ к элементу по имени ключа в стиле PHP массивов – `$service['foo.name']`.
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

Необходимо внедрить в параметр `\App\Services\Foo::$services` «ленивую коллекцию», где ключами элементов будут выступать идентификатор контейнера [^ContainerId] элемента.

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
            servies: diTaggedAs('tags.access_by_key')
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
use function is_a;

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
use function is_a;

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

## Ключ в коллекции как целое число { #key-as-int }

Внедряемая коллекция может быть представлен простым списком, где ключи элементов представлены целыми числами от `0` до `n`.

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
                'tags.access_by_key',
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
            'tags.access_by_key',
            useKeys: false,// [!code ++]
        )]
        public readonly iterable $services
    ) {}    
}
```

## Ключ элемента коллекции в метаданных { #key-in-metadata }

Ключ для элемента коллекции отличный от идентификатора контейнера [^ContainerId] можно получить через [метаданные](tag-metadata.md) тега.

Значение в метаданных тега указывается парой ключ-значение:
- **ключ** – произвольная непустая строка, одинаковая во всех тегах коллекции.
- **значение** – не пустая строка уникальная в рамках коллекции.

Указываем значение ключа из метаданных тегов в параметре `$key` [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md) или [атрибуте `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md).

Указываем метаданные тегов через параметр `$options` в методе `bindTag()` [^BindTag] или [атрибуте `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md).

### Пример использования ключа элемента из метаданных { #example-key-in-metadata }

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

Необходимо внедрить в параметр `\App\Services\Foo::$services` «ленивую коллекцию», где ключи элементов коллекции будут получены из метаданных каждого тега.
Для получения ключа элемента из [метаданных тега](tag-metadata.md) будем указывать значение `'key_as'`.


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
                'tags.key_from_metadata',
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
use function is_a;

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
            'tags.key_from_metadata',// [!code ++]
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

## «Метод ключа» элемента коллекции { #key-from-method }

Ключ элемента коллекции, представленного PHP классом, можно получить через специальный метод этого PHP класса – «метод ключа». Настройка производится через [метаданные тега](tag-metadata.md).

Значение в метаданных тега указывается парой ключ-значение:
- **ключ** – произвольная непустая строка, одинаковая во всех тегах коллекции.
- **значение** – имя «метода ключа» с префиксом `self::`.

Указываем значение ключа из метаданных тегов в параметре `$key` [хелпер функции `diTaggedAs()`](../php-definition/di-tagged-as.md) или [атрибуте `\Kaspi\DiContainer\Attributes\TaggedAs`](../attribute-definition/tagged-as.md).

Указываем метаданные тегов через параметр `$options` в методе `bindTag()` [^BindTag] или [атрибуте `\Kaspi\DiContainer\Attributes\Tag`](../attribute-definition/tag.md).

<!--@include: _include/key_from_method.md-->

### Пример использования «Метод ключа» элемента коллекции { #example-key-from-method }

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

Необходимо внедрить в параметр `\App\Services\Foo::$services` «ленивую коллекцию», где ключи элементов коллекции будут получены из «**метода ключа**» каждого элемента коллекции. Ключ в [метаданных тегов](tag-metadata.md) указывающий какой метод вызвать – `'key_as'`.

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
                'tags.key_from_method',
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
use function is_a;

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
            'tags.key_from_method',// [!code ++]
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

## «Метод ключа» элемента коллекции по умолчанию { #key-from-method-default }

## Совпадение ключей элементов в коллекции { #keys-equals }


------------

🚧🚧🚧🚧🚧🚧🚧🚧

⚖ Пример поведения коллекции если значения ключей совпадают:
```php
// src/Classes/ServiceOne.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.tag_one', options: ['key_as' => 'foo'])]
class ServiceOne {}
```
```php
// src/Classes/ServiceTwo.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag('tags.tag_one', options: ['key_as' => 'foo'], priority: 100)]
class ServiceTwo {}
```
```php
// src/Classes/ClassTaggedAs.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\TaggedAs;

class ClassTaggedAs {

    public function __construct(
        // ключ будет получен из метаданных тега - $options['key_as']
        #[TaggedAs('tags.tag_one', key: 'key_as')]
        private iterable $items
    ) {}
}
```
> [!NOTE]
> Так как оба тегированных класса содержат в метаданных (в `$options`) одинаковые значения `'key_as' => 'foo'`,
> то при получении коллекции в свойство `ClassTaggedAs::$items` по тегу будет учтён приоритет определения (`priority`). У класса `ServiceTwo`
> приоритет `100`, а у класса `ServiceOne` приоритет не определен – таким образом при одинаковых ключах у определений
> в коллекцию попадёт только `ServiceTwo` у которого приоритет `100`.

### Ключ из метода класса по умолчанию.
Так же предусмотрено объявление метода для php класса,
через который будет произведена попытка получить ключ для коллекции
если у тегированного определения не указан ключ для коллекции в метаданных (_в `$options`_).

Указать метод получения ключа по умолчанию можно через аргумент
`$keyDefaultMethod` [в хелпер функции diTaggedAs](../03-php-definition.md#ditaggedas)
или у [php атрибута #[TaggedAs]](../02-attribute-definition.md#taggedas).

> [!IMPORTANT]
> Метод реализующий получение ключа должен быть объявлен как `public static function` и возвращать тип `string`.
> В качестве аргументов метод принимает два параметра:
> - `string $tag` – имя тега;
> - `array $options` – метаданные тега;

Пример получения ключа коллекции по интерфейсу:
```php
// src/Classes/ServiceOne.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag;

class ServiceOne implements ServiceInterface {

    public static function getServiceKey(): string {
        return 'bar';
    }

}
```
```php
// src/Classes/ServiceTwo.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\Tag;

class ServiceTwo implements ServiceInterface {

    public static function getServiceKey(): string {
        return 'foo';
    }

}
```
```php
// src/Classes/ClassTaggedAs.php
namespace App\Classes;

use Kaspi\DiContainer\Attributes\TaggedAs;

class ClassTaggedAs {

    public function __construct(
        #[TaggedAs(ServiceInterface::class, keyDefaultMethod: 'getServiceKey')]
        private iterable $items
    ) {}

}
```
> [!NOTE]
> При разрешении аргументов конструктора класса `App\Rules\ClassTaggedAs::class` в свойстве `ClassTaggedAs::$items`
> содержится итерируемая «ленивая» коллекция из
> классов `ServiceOne` с ключём `bar` полученный из метода `ServiceOne::getServiceKey()`,
> `ServiceTwo` с ключём `foo` полученный из метода `ServiceTwo::getServiceKey()`.

<!--@include: ../_include/term_notes.md-->
<!--@include: ../php-definition/_include/bind-tag_notes.md-->
