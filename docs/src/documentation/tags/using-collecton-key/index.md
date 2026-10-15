---
outline: [2, 4]
---
# 🔑 Идентификаторы элементов коллекции { #title }

## Обзор { #overview }

Внедряемая коллекция по умолчанию в качестве идентифкаторов элементов получает идентификатор контейнера [^ContainerId] этого элемента, что позволяет обращаться к элементам коллекции в [произвольном порядке](#key-access).

Через настройки внедрения коллекции и [метаданные тега](../tag-metadata.md) можно указывать пользовательские идентификаторы элементам коллекции:
- [Идентификатор элемента как целое число](key-as-int.md).
- [Идентификатор элемента в метаданных тега](key-in-metadata.md).
- [«Метод идентификатора» элемента коллекции](key-from-method.md) для элемента коллекции PHP класс.
- [«Метод идентификатора» элемента коллекции по умолчанию](key-from-method-default.md) для элемента коллекции PHP класс.

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

### Настройка в файлах конфигураций { #example-inject-lazy-collection-with-key-assess-php-definitions }

Внедрение в параметр `\App\Services\Foo::$services` через [хелпер функцию `diTaggedAs()`](../../php-definition/di-tagged-as.md). PHP классам добавим теги через метод `bindTag()` [^BindTag].

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
        ->bindTag('tags.access_by_key');
   
   yield diAutowire(Baz::class)
        ->bindTag('tags.access_by_key');
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

### Конфигурация через атрибуты { #example-inject-lazy-collection-with-key-assess-attributes }

Внедрение в параметр `\App\Services\Foo::$services` через [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md).
Добавим PHP классам теги через [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md).

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

<!--@include: ../../_include/term_notes.md-->
<!--@include: ../../php-definition/_include/bind-tag_notes.md-->
