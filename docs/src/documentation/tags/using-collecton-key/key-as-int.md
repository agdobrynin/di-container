---
outline: [2, 4]
---
# Идентификатор элемента как целое число { #key-as-int }

## Обзор

Внедряемая коллекция может быть представлен простым списком, где идентификаторы элементов представлены целыми числами от `0` до `n`.

## Пример внедрения коллекции-списка { #example }

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

Необходимо внедрить в параметр `\App\Services\Foo::$services` «ленивую коллекцию», где идентификаторы элементов коллекции предствлены целыми числами от `0` до `n`.

### Настройка в файлах конфигураций { #example-php-definition }

Внедрение в параметр `\App\Services\Foo::$services` через [хелпер функцию `diTaggedAs()`](../../php-definition/di-tagged-as.md) с параметром `$useKeys` равным `false`. PHP классам добавим теги через метод `bindTag()` [^BindTag].

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
                tag: 'tags.qux',
                useKeys: false,
        );

    yield diAutowire(Bar::class)
        ->bindTag('tags.qux');
   
   yield diAutowire(Baz::class)
        ->bindTag('tags.qux');
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
// int(0)
//string(16) "App\Services\Bar"
// int(1)
//string(16) "App\Services\Baz"
```

### Конфигурация через атрибуты { #example-attributes }

Внедрение в параметр `\App\Services\Foo::$services` через [атрибут `\Kaspi\DiContainer\Attributes\TaggedAs`](../../attribute-definition/tagged-as.md) с параметром `$useKeys` равным `false`. Добавим PHP классам теги через [атрибут `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md).

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
            name: 'tags.qux',// [!code ++]
            useKeys: false,// [!code ++]
        )]// [!code ++]
        public readonly iterable $services
    ) {}    
}
```

```php [Bar.php]
//file: /app/src/Services/Bar.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag('tags.qux')]// [!code ++]
final class Bar
{
    // …
}
```

```php [Baz.php]
//file: /app/src/Services/Baz.php
namespace App\Services;

use Kaspi\DiContainer\Attributes\Tag;// [!code ++]

#[Tag('tags.qux')]// [!code ++]
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
// int(0)
//string(16) "App\Services\Bar"
// int(1)
//string(16) "App\Services\Baz"
```

<!--@include: ../../php-definition/_include/bind-tag_notes.md-->
