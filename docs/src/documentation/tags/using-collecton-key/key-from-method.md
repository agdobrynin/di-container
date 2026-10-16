---
outline: [2, 4]
---
# «Метод идентификатора» элемента коллекции { #key-from-method }

## Обзор { #overview }

Идентификатор элемента коллекции, представленного PHP классом, можно получить через специальный метод этого PHP класса – «метод идентификатора». Настройка производится через [метаданные тега](../tag-metadata.md).

Значение в метаданных тега указывается парой ключ-значение:
- **ключ** – имя метаданных, одинаковое во всех тегах коллекции.
- **значение** – «метод идентификатора» с префиксом `self::`.

Внедрение коллекции через [хелпер функцию diTaggedAs()](../../php-definition/di-tagged-as.md) или [атрибут \Kaspi\DiContainer\Attributes\TaggedAs](../../attribute-definition/tagged-as.md) с передачей имени метаданных в параметре `$key`.

Метаданные тега передаются в параметре `$options` метода `bindTag()` [^BindTag] или [атрибута `\Kaspi\DiContainer\Attributes\Tag`](../../attribute-definition/tag.md).

<!--@include: _include/key_from_method.md-->
<!--@include: _include/important_key_equals.md-->

## Пример использования «Метод идентификатора» элемента коллекции { #example-key-from-method }

Необходимо внедрить в параметр `\App\Services\Foo::$services` «ленивую коллекцию», где идентификаторы элементов коллекции будут получены из «**метода идентификатора**» каждого элемента коллекции.

Установим имя метаданных – `'key_as'`.

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

### Настройка в файлах конфигураций { #example-key-from-method-php-definitions }

Внедрение в параметр `\App\Services\Foo::$services` через [хелпер функцию `diTaggedAs()`](../../php-definition/di-tagged-as.md) и передачей имени метаданных в параметр `$key`.

PHP классам добавим теги с метаданными через метод `bindTag()` [^BindTag].


```php {12,19-21,27-30}
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

### Конфигурация через атрибуты { #example-key-from-method-php-attributes }

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

<!--@include: ../../_include/term_notes.md-->
<!--@include: ../../php-definition/_include/bind-tag_notes.md-->