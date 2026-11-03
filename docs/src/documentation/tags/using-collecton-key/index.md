---
outline: [2, 4]
---
# Идентификаторы элементов коллекции { #title }

## Обзор { #overview }

Внедряемая коллекция по умолчанию в качестве идентификаторов элементов получает идентификатор контейнера [^ContainerId] этого элемента, что позволяет обращаться к элементам коллекции в [произвольном порядке](#key-access).

Через настройки внедрения коллекции и [метаданные тега](../tag-metadata.md) можно указывать пользовательские идентификаторы элементам коллекции:
- [Идентификатор элемента как целое число](key-as-int.md).
- [Идентификатор элемента в метаданных тега](key-in-metadata.md).
- [«Метод идентификатора» элемента коллекции](key-from-method.md) для элемента коллекции PHP-класс.
- [«Метод идентификатора» элемента коллекции по умолчанию](key-from-method-default.md) для элемента коллекции PHP-класс.

> [!WARNING]
> При обращении к элементу коллекции по строковому идентификатору может быть выброшено исключение с типом `Psr\Container\NotFoundExceptionInterface`, если элемент коллекции не найден.

<!--@include: _include/key_from_method.md-->

## Произвольный доступ к элементам коллекции { #key-access }

При внедрении коллекции через [хелпер функцию `diTaggedAs()`](../../php-definition/di-tagged-as.md) или [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) параметр `$isLazy` по умолчанию имеет значение `true`,
что означает – внедряемый объект будет «[ленивой коллекцией](../index.md#collection-is-lazy)».

«Ленивая коллекция» реализует интерфейсы:
- `\Iterator`
- `\Psr\Container\ContainerInterface`
- `\ArrayAccess`
- `\Countable`

Реализация этих интерфейсов даёт возможность:
- Перебор элементов коллекции через [языковую конструкцию `foreach`](https://www.php.net/manual/en/control-structures.foreach.php).
- Доступ к элементу по идентификатору в стиле PHP массивов – `$service['foo.name']`.
- Доступ к элементу через методы `get()` и `has()` описанные в [PSR-11](https://www.php-fig.org/psr/psr-11/).

## Пример внедрения коллекции { #example-inject-lazy-collection-with-key-assess }

PHP-классы:

::: code-group

```php [Foo.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php // [!code highlight]

namespace App\Services;

final class Foo
{
    public function __construct(
        public readonly iterable $services
    ) {}
}
```

```php [Bar.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php // [!code highlight]

namespace App\Services;

final class Bar
{
    // …
}
```

```php [Baz.php]
<?php
declare(strict_types=1);

// file: /app/src/Services/Baz.php // [!code highlight]

namespace App\Services;

final class Baz
{
    // …
}
```

:::

Необходимо внедрить в параметр конструктора `$services` PHP-класса `\App\Services\Foo` «ленивую коллекцию» с доступом к элементам коллекции по идентификатору контейнера [^ContainerId].

### Настройка в файлах конфигураций { #example-inject-lazy-collection-with-key-assess-php-definitions }

Объявить внедрение «ленивой» коллекции элементов отмеченных тегом `'tags.access_by_key'` в параметр `$services` PHP-класса `\App\Services\Foo` через [хелпер функцию `diTaggedAs()`](../../php-definition/di-tagged-as.md) в файле конфигураций `services.php`. В качестве ключа каждого элемента коллекции должен быть идентификатор контейнера этого элемента, для этого параметр хелпер функции `$useKeys` принимает значение `true` (значение по умолчанию). 

В файле конфигураций `tagged_services.php` PHP-классам представляющих элементы коллекции объявим тег `'tags.access_by_key'` через метод `bindTag()` [^BindTag].

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->load('/app/config/services.php')// [!code highlight]
    ->load('/app/config/tagged_services.php')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);

// доступ к элементу коллекции через методы `\Psr\Container\ContainerInterface`
$bar = $foo->services->get(Bar::class);

var_dump($bar::class);

// доступ к элементу коллекции через методы `\ArrayAccess`
$baz = $foo->services[Baz::class];

var_dump($baz::class);

// подсчёт количества элементов коллекции через `\Countable`
var_dump(count($foo->services));
```

```php [services.php]
<?php
declare(strict_types=1);

// file: /app/config/services.php // [!code highlight]

use App\Services\Foo;

use function \Kaspi\DiContainer\{diAutowire, diTaggedAs};

return static function (): Generator {
    yield diAutowire(Foo::class)
        ->bindArguments(
            servies: diTaggedAs('tags.access_by_key')//[!code ++]
        );
};
```

```php [tagged_services.php]
<?php
declare(strict_types=1);

// file: /app/config/tagged_services.php  // [!code highlight]

use App\Services\{Bar, Baz};

use function \Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(Bar::class)
        ->bindTag('tags.access_by_key');//[!code ++]
   
   yield diAutowire(Baz::class)
        ->bindTag('tags.access_by_key');//[!code ++]
};
```

::: 

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
string(16) "App\Services\Bar"
string(16) "App\Services\Baz"
int(2)
```

### Конфигурация через атрибуты { #example-inject-lazy-collection-with-key-assess-attributes }

Объявить внедрение коллекции элементов отмеченных тегом `'tags.access_by_key'` в параметр `$services` PHP-класса `\App\Services\Foo` через [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md). В качестве ключа каждого элемента коллекции должен быть идентификатор контейнера этого элемента, для этого параметр атрибута `$useKeys` принимает значение `true` (значение по умолчанию).

Объявим тег `'tags.access_by_key'` PHP-классам через [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md).

PHP-классы:

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

#[Tag('tags.access_by_key')]// [!code ++]
final class Bar
{
    // …
}
```

```php [Baz.php]
//file: /app/src/Services/Baz.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag('tags.access_by_key')]// [!code ++]
final class Baz
{
    // …
}
```

:::

::: code-group

```php [container_build.php]
<?php
declare(strict_types=1);

// file: /app/src/container_build.php // [!code highlight]

require_once __DIR__.'/../vendor/autoload.php';//[!code warning]

use Kaspi\DiContainer\DiContainerBuilder;
use App\Services\Foo;

$container = (new DiContainerBuilder())
    ->import(namespace: 'App\\', src: '/app/src')// [!code highlight]
    ->build();

$foo = $container->get(Foo::class);

// доступ к элементу коллекции через методы `\Psr\Container\ContainerInterface`
$bar = $foo->services->get(Bar::class);

var_dump($bar::class);

// доступ к элементу коллекции через методы `\ArrayAccess`
$baz = $foo->services[Baz::class];

var_dump($baz::class);

// подсчёт количества элементов коллекции через `\Countable`
var_dump(count($foo->services));
```

:::

Выполните в терминале команду:

```shell
php /app/src/container_build.php
```

Появится сообщение вида:

```console
string(16) "App\Services\Bar"
string(16) "App\Services\Baz"
int(2)
```

<!--@include: ../../_include/term_notes.md-->
<!--@include: ../../php-definition/_include/bind_notes.md-->
